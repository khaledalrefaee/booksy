<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The Daily Business Summary email for one company, for one day.
 *
 * Queued (never sent inside a request): the send command pushes it onto the
 * database queue and the worker renders + delivers it. The report payload is
 * built by {@see \App\Services\DailyBusinessSummaryService} and passed in whole.
 *
 * "From" is the platform default (config('mail.from')) — this is a GlowRez
 * system email, not an owner broadcast.
 */
class DailyBusinessSummaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string,mixed>  $report  Output of DailyBusinessSummaryService::build()
     */
    public function __construct(public array $report)
    {
    }

    public function envelope(): Envelope
    {
        $isAr = $this->report['is_ar'] ?? true;
        $name = $this->report['company']->localizedName();
        $date = $this->report['date']->format('Y-m-d');

        $subject = $isAr
            ? "ملخص أعمال {$name} ليوم {$date}"
            : "Daily summary for {$name} — {$date}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        // Multipart (HTML + text) is less likely to be flagged as promotional.
        return new Content(
            view: 'emails.daily-business-summary',
            text: 'emails.daily-business-summary-text',
            with: ['r' => $this->report],
        );
    }
}
