<?php

namespace App\Services\Sms;

use App\Models\Branch;
use App\Models\Company;
use App\Models\SmsCreditBatch;
use App\Models\SmsMessage;
use App\Models\SmsPackage;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Services\StaffNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Owns the credit ledger: wallet resolution (branch → company pool fallback),
 * granting/purchasing credits, and the atomic FIFO consume/refund used at send
 * time. Every movement writes an sms_transactions row so the owner has a full,
 * auditable trail of how each SMS was added and used.
 */
class SmsCreditService
{
    public function firstOrCreateWallet(int $companyId, ?int $branchId = null): SmsWallet
    {
        return SmsWallet::firstOrCreate(
            ['company_id' => $companyId, 'branch_id' => $branchId],
            [
                'balance'               => 0,
                'low_balance_threshold' => (int) config('booksy.sms.credits.low_balance_threshold', 50),
            ],
        );
    }

    /**
     * The wallet that should back a branch's sends: its own wallet if it holds
     * credits, otherwise the shared company pool. Returns whichever wallet best
     * describes the branch's balance even when empty (for recording a skip);
     * null only when neither wallet exists yet.
     */
    public function contextWalletFor(Branch $branch): ?SmsWallet
    {
        $branchWallet = $branch->smsWallet()->first();
        if ($branchWallet && $branchWallet->hasCredits()) {
            return $branchWallet;
        }

        $pool = SmsWallet::where('company_id', $branch->company_id)->whereNull('branch_id')->first();
        if ($pool && $pool->hasCredits()) {
            return $pool;
        }

        // Nothing has credits — return whatever exists so the skip is attributed.
        return $branchWallet ?: $pool;
    }

    /** Owner adds free credits to a wallet (e.g. 200 SMS to a branch). */
    public function grant(SmsWallet $wallet, int $credits, array $opts = []): SmsCreditBatch
    {
        return $this->addCredits($wallet, $credits, 'grant', $opts);
    }

    /** Company buys a package — credits added, priced, and dated to the package. */
    public function purchase(SmsWallet $wallet, SmsPackage $package): SmsCreditBatch
    {
        return $this->addCredits($wallet, $package->credits, 'purchase', [
            'package_id' => $package->id,
            'price'      => $package->price,
            'expires_at' => $package->validity_days
                ? now()->addDays($package->validity_days)
                : null,
            'note'       => $package->name,
        ]);
    }

    private function addCredits(SmsWallet $wallet, int $credits, string $source, array $opts): SmsCreditBatch
    {
        return DB::transaction(function () use ($wallet, $credits, $source, $opts) {
            $wallet = SmsWallet::lockForUpdate()->find($wallet->id);

            $expiresAt = $opts['expires_at'] ?? null;
            if (is_string($expiresAt)) {
                $expiresAt = Carbon::parse($expiresAt);
            }

            $batch = SmsCreditBatch::create([
                'wallet_id'           => $wallet->id,
                'source'              => $source,
                'package_id'          => $opts['package_id'] ?? null,
                'credits'             => $credits,
                'remaining'           => $credits,
                'price'               => $opts['price'] ?? null,
                'expires_at'          => $expiresAt,
                'note'                => $opts['note'] ?? null,
                'created_by_owner_id' => $opts['owner_id'] ?? null,
            ]);

            $wallet->balance         += $credits;
            $wallet->total_purchased += $credits;
            // Fresh credits clear the "we already warned you" throttles.
            $wallet->notified_low_at  = null;
            $wallet->notified_zero_at = null;
            $wallet->save();

            SmsTransaction::create([
                'wallet_id'           => $wallet->id,
                'batch_id'            => $batch->id,
                'package_id'          => $opts['package_id'] ?? null,
                'type'                => $source === 'purchase' ? 'purchase' : 'grant',
                'credits'             => $credits,
                'balance_after'       => $wallet->balance,
                'created_by'          => ($opts['owner_id'] ?? null) ? 'owner' : 'system',
                'created_by_owner_id' => $opts['owner_id'] ?? null,
                'meta'                => $opts['meta'] ?? null,
            ]);

            return $batch;
        });
    }

    /**
     * Atomically charge a wallet for a sent message, drawing FIFO across its
     * batches. Returns true when fully charged; false (and no change) when the
     * wallet can't cover it, so the caller must not treat the SMS as sent.
     */
    public function consume(SmsWallet $wallet, int $credits, SmsMessage $message): bool
    {
        if ($credits <= 0) {
            return true;
        }

        return DB::transaction(function () use ($wallet, $credits, $message) {
            $wallet = SmsWallet::lockForUpdate()->find($wallet->id);

            if (! $wallet || $wallet->balance < $credits) {
                return false;
            }

            $need    = $credits;
            $batches = SmsCreditBatch::where('wallet_id', $wallet->id)
                ->consumable()->lockForUpdate()->get();

            foreach ($batches as $batch) {
                if ($need <= 0) break;
                $take = min($need, $batch->remaining);
                $batch->decrement('remaining', $take);
                $need -= $take;
            }

            if ($need > 0) {
                // Ledger says enough but dated batches couldn't cover it — abort clean.
                return false;
            }

            $wallet->balance    -= $credits;
            $wallet->total_used += $credits;
            $wallet->save();

            SmsTransaction::create([
                'wallet_id'      => $wallet->id,
                'sms_message_id' => $message->id,
                'type'           => 'consume',
                'credits'        => -$credits,
                'balance_after'  => $wallet->balance,
                'created_by'     => 'system',
                'meta'           => ['message_type' => $message->message_type],
            ]);

            $this->notifyIfLow($wallet);

            return true;
        });
    }

    /** Give charged credits back (used when a send fails after consuming). */
    public function refund(SmsWallet $wallet, int $credits, SmsMessage $message): void
    {
        if ($credits <= 0) {
            return;
        }

        DB::transaction(function () use ($wallet, $credits, $message) {
            $wallet = SmsWallet::lockForUpdate()->find($wallet->id);
            if (! $wallet) return;

            $wallet->balance    += $credits;
            $wallet->total_used  = max(0, $wallet->total_used - $credits);
            $wallet->save();

            SmsTransaction::create([
                'wallet_id'      => $wallet->id,
                'sms_message_id' => $message->id,
                'type'           => 'refund',
                'credits'        => $credits,
                'balance_after'  => $wallet->balance,
                'created_by'     => 'system',
                'meta'           => ['reason' => 'send_failed'],
            ]);
        });
    }

    /**
     * Fire an in-app alert once when a wallet crosses its low-balance line or
     * hits zero. The notified_* throttles stop it repeating on every send.
     */
    private function notifyIfLow(SmsWallet $wallet): void
    {
        if (! $wallet->notify_low_balance) {
            return;
        }

        if ($wallet->balance <= 0 && $wallet->notified_zero_at === null) {
            StaffNotificationService::smsBalanceEmpty($wallet);
            $wallet->forceFill(['notified_zero_at' => now()])->save();
            return;
        }

        if ($wallet->isLow() && $wallet->notified_low_at === null) {
            StaffNotificationService::smsBalanceLow($wallet);
            $wallet->forceFill(['notified_low_at' => now()])->save();
        }
    }
}
