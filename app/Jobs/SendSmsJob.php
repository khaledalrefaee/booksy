<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Models\SmsSetting;
use App\Models\SmsWallet;
use App\Services\Sms\RasselClient;
use App\Services\Sms\SmsCreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Delivers one SMS on the queue so booking never waits on Rasel. Credits are
 * charged against the COMPANY's GlowRez wallet BEFORE the send (no unpaid
 * messages, no negative balance) and refunded if the provider hard-rejects it.
 *
 * The charge is idempotent per message (SmsCreditService::isCharged) so a
 * retry — whether from a transient provider error or a re-dispatch — never
 * bills the wallet twice; Rasel's own Idempotency-Key (the message dedupe_key)
 * guards against a double delivery. GlowRez remains the source of truth for the
 * company's balance; Rasel's estimated cost is recorded for reference only.
 */
class SendSmsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $uniqueFor = 3600;

    public function __construct(public int $messageId, public int $credits) {}

    public function uniqueId(): string
    {
        return 'sms-message:' . $this->messageId;
    }

    public function handle(RasselClient $client, SmsCreditService $credits): void
    {
        $message = SmsMessage::find($this->messageId);
        if (! $message || $message->status === 'sent' || $message->status === 'skipped') {
            return; // already delivered or intentionally skipped
        }

        $wallet = $message->wallet_id ? SmsWallet::find($message->wallet_id) : null;

        // Charge first — but only once. A retry of the same message that is still
        // paid for must not re-consume the company's credits.
        if ($this->credits > 0 && ! $credits->isCharged($message)) {
            if (! $wallet || ! $credits->consume($wallet, $this->credits, $message)) {
                $message->update([
                    'status'         => 'skipped',
                    'failure_reason' => 'insufficient_credits',
                ]);
                return;
            }
        }

        $result = $client->send($message->phone, $message->body, [
            'idempotencyKey' => $this->idempotencyKey($message),
            'messageType'    => $message->raselMessageType(),
            'senderId'       => SmsSetting::current()->default_sender_id ?: null,
        ]);

        // Accepted (HTTP 200 sent / 202 queued). Keep the charge, record tracking.
        if ($result['ok'] ?? false) {
            $providerStatus = $result['provider_status'] ?? 'sent';

            $message->update([
                'status'              => $providerStatus === 'queued' ? 'queued' : 'sent',
                'provider_status'     => $providerStatus,
                'provider_message_id' => $result['message_id'] ?? null,
                'request_id'          => $result['request_id'] ?? null,
                'usage_id'            => $result['usage_id'] ?? null,
                'queue_id'            => $result['queue_id'] ?? null,
                'resolved_provider'   => $result['resolved_provider'] ?? null,
                'sender_source'       => $result['sender_source'] ?? null,
                'estimated_cost'      => $result['estimated_cost'] ?? null,
                'cost_currency'       => $result['currency'] ?? null,
                'credits_used'        => $this->credits,
                'sent_at'             => now(),
                'failure_reason'      => null,
                'error_code'          => null,
            ]);
            return;
        }

        // Transient (429/502/503/network) — keep the charge and let the queue
        // retry. Honor Retry-After when Rasel provides it.
        if (($result['retry'] ?? false) && $this->attempts() < $this->tries) {
            $delay = (int) ($result['retry_after'] ?? ($this->backoff[$this->attempts() - 1] ?? 120));
            $message->update([
                'error_code'     => $result['code'] ?? null,
                'request_id'     => $result['request_id'] ?? $message->request_id,
                'failure_reason' => Str::limit('retrying: ' . (string) ($result['error'] ?? ''), 500),
            ]);
            $this->release(max(5, $delay));
            return;
        }

        // Hard failure (or retries exhausted) — return the credits and record why.
        if ($this->credits > 0 && $wallet && $credits->isCharged($message)) {
            $credits->refund($wallet, $this->credits, $message);
        }

        $message->update([
            'status'         => 'failed',
            'error_code'     => $result['code'] ?? null,
            'request_id'     => $result['request_id'] ?? $message->request_id,
            'failure_reason' => Str::limit((string) ($result['error'] ?? 'send failed'), 500),
        ]);

        Log::warning("SMS send failed (message {$message->id}) [{$result['code']}]: {$result['error']}");
    }

    /**
     * Stable idempotency key for this message. Prefers the logical dedupe_key
     * (one per booking visit) so even a fresh dispatch can't double-deliver;
     * falls back to the message id.
     */
    private function idempotencyKey(SmsMessage $message): string
    {
        return 'glowrez-sms:' . ($message->dedupe_key ?: ('m' . $message->id));
    }

    /**
     * Final give-up after retries (e.g. an unexpected exception): ensure the row
     * isn't left stuck on "queued", and return any credits still charged so an
     * undelivered message never costs the company.
     */
    public function failed(\Throwable $e): void
    {
        $message = SmsMessage::find($this->messageId);
        if (! $message || ! in_array($message->status, ['queued'], true)) {
            return;
        }

        $credits = app(SmsCreditService::class);
        $wallet  = $message->wallet_id ? SmsWallet::find($message->wallet_id) : null;

        if ($this->credits > 0 && $wallet && $credits->isCharged($message)) {
            $credits->refund($wallet, $this->credits, $message);
        }

        $message->update([
            'status'         => 'failed',
            'failure_reason' => Str::limit($e->getMessage(), 500),
        ]);
    }
}
