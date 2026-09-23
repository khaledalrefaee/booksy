<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A short, warm greeting sent on a day the business is closed (weekly day off
 * or a company holiday) in place of the stats summary. Same GlowRez identity,
 * queued like the summary.
 */
class HolidayGreetingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public bool $isAr = true,
        public ?string $holidayName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $name = $this->company->localizedName();

        $subject = $this->isAr
            ? "يوم عطلة سعيد — {$name}"
            : "Enjoy your day off — {$name}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.holiday-greeting',
            text: 'emails.holiday-greeting-text',
            with: [
                'company'     => $this->company,
                'isAr'        => $this->isAr,
                'holidayName' => $this->holidayName,
            ],
        );
    }
}
