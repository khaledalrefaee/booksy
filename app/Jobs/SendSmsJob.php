<?php

namespace App\Jobs;

use App\Models\SmsMessage;
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

/**
 * Delivers one SMS on the queue so booking never waits on Rassel. Credits are
 * charged atomically BEFORE the send (no unpaid messages, no negative balance)
 * and refunded if the provider rejects it. ShouldBeUnique + the message status
 * check together guarantee a given message is never sent — or charged — twice,
 * even if the job is dispatched or retried more than once.
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

        // Charge first. If the wallet can't cover it now, don't send.
        if ($this->credits > 0) {
            if (! $wallet || ! $credits->consume($wallet, $this->credits, $message)) {
                $message->update([
                    'status'         => 'skipped',
                    'failure_reason' => 'insufficient_credits',
                ]);
                return;
            }
        }

        [$ok, $providerId, $error] = $client->send($message->phone, $message->body);

        if ($ok) {
            $message->update([
                'status'              => 'sent',
                'provider_message_id' => $providerId,
                'credits_used'        => $this->credits,
                'sent_at'             => now(),
                'failure_reason'      => null,
            ]);
            return;
        }

        // Provider rejected it — return the credits and record why.
        if ($this->credits > 0 && $wallet) {
            $credits->refund($wallet, $this->credits, $message);
        }

        $message->update([
            'status'         => 'failed',
            'failure_reason' => \Illuminate\Support\Str::limit((string) $error, 500),
        ]);

        Log::warning("SMS send failed (message {$message->id}): {$error}");
    }

    /** Final give-up after retries: ensure the row isn't left stuck on "queued". */
    public function failed(\Throwable $e): void
    {
        $message = SmsMessage::find($this->messageId);
        if ($message && $message->status === 'queued') {
            $message->update([
                'status'         => 'failed',
                'failure_reason' => \Illuminate\Support\Str::limit($e->getMessage(), 500),
            ]);
        }
    }
}
