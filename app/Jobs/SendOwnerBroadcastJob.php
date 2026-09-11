<?php

namespace App\Jobs;

use App\Mail\OwnerBroadcastMail;
use App\Models\OwnerEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers one owner broadcast to its whole recipient list off-request, so the
 * compose screen never waits on SMTP. Best-effort per recipient: a single bad
 * address is logged and skipped, never aborting the rest. tries=1 so a mid-way
 * failure can't re-send to everyone who already received it.
 */
class SendOwnerBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800;

    /** ~600ms between sends → under Resend's 2 req/s limit. */
    private const THROTTLE_MICROSECONDS = 600000;

    public function __construct(public int $ownerEmailId, public bool $isAr) {}

    public function handle(): void
    {
        $email = OwnerEmail::find($this->ownerEmailId);
        if (! $email || $email->status !== 'queued') {
            return; // already processed or gone
        }

        $email->update(['status' => 'sending']);

        $sent = 0;
        $failed = 0;
        $first = true;
        $lastError = null;

        foreach ($email->recipients ?? [] as $recipient) {
            $address = $recipient['email'] ?? null;
            if (! $address) {
                continue;
            }

            // Throttle to stay under Resend's default 2 requests/second limit,
            // which otherwise 429-rejects every send after the first couple.
            if (! $first) {
                usleep(self::THROTTLE_MICROSECONDS);
            }
            $first = false;

            $error = $this->sendOne($email, $address);
            if ($error === null) {
                $sent++;
            } else {
                $failed++;
                $lastError = $error;
            }
        }

        $email->update([
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'status'       => $failed === 0 ? 'sent' : ($sent > 0 ? 'partial' : 'failed'),
            'last_error'   => $lastError ? \Illuminate\Support\Str::limit($lastError, 480) : null,
        ]);
    }

    /**
     * Send to one address; on failure (usually a transient 429 or DNS blip), wait
     * a beat and retry once before giving up. Never throws — a bad address is
     * logged and the broadcast moves on. Returns null on success, or the error
     * message on failure (so the caller can surface it to the owner).
     */
    private function sendOne(OwnerEmail $email, string $address): ?string
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                Mail::to($address)->send(new OwnerBroadcastMail(
                    $email->subject,
                    $email->body,
                    $email->from_address,
                    $email->from_name ?? '',
                    $this->isAr,
                ));

                return null;
            } catch (\Throwable $e) {
                if ($attempt === 1) {
                    usleep(self::THROTTLE_MICROSECONDS * 2); // back off, then retry once
                    continue;
                }
                Log::warning("Owner broadcast email failed to {$address}: {$e->getMessage()}");

                return $e->getMessage();
            }
        }

        return 'send failed';
    }

    public function failed(\Throwable $e): void
    {
        $email = OwnerEmail::find($this->ownerEmailId);
        if ($email && in_array($email->status, ['queued', 'sending'], true)) {
            $email->update(['status' => $email->sent_count > 0 ? 'partial' : 'failed']);
        }
    }
}
