<?php

namespace App\Console\Commands;

use App\Models\SmsCreditBatch;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmsExpireCredits extends Command
{
    protected $signature = 'sms:expire-credits';
    protected $description = 'Expire SMS credit batches past their validity and reconcile wallet balances';

    public function handle(): int
    {
        $batches = SmsCreditBatch::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('remaining', '>', 0)
            ->get();

        $expired = 0;

        foreach ($batches as $batch) {
            DB::transaction(function () use ($batch, &$expired) {
                $batch = SmsCreditBatch::lockForUpdate()->find($batch->id);
                if (! $batch || $batch->remaining <= 0) return;

                $wallet = SmsWallet::lockForUpdate()->find($batch->wallet_id);
                if (! $wallet) return;

                $lost = $batch->remaining;
                $batch->update(['remaining' => 0]);

                $wallet->balance = max(0, $wallet->balance - $lost);
                $wallet->save();

                SmsTransaction::create([
                    'wallet_id'     => $wallet->id,
                    'batch_id'      => $batch->id,
                    'type'          => 'expire',
                    'credits'       => -$lost,
                    'balance_after' => $wallet->balance,
                    'created_by'    => 'system',
                    'meta'          => ['expired_at' => $batch->expires_at?->toIso8601String()],
                ]);

                $expired += $lost;
            });
        }

        $this->info("Expired {$expired} SMS credit(s).");
        return self::SUCCESS;
    }
}
