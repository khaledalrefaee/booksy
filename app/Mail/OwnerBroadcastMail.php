<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A free-form broadcast email composed by the platform owner in /owner/emails.
 * Branded GlowRez shell (logo + olive/gold header); the body is the owner's
 * own plain text, rendered with line breaks preserved.
 *
 * The "From" identity is chosen per-send from config('mail.owner_senders').
 */
class OwnerBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public string $fromAddress,
        public string $fromName,
        public bool $isAr,
    ) {
    }

    public function envelope(): Envelope
    {
        $replyTo = config('mail.owner_reply_to');

        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            replyTo: $replyTo ? [new Address($replyTo)] : [],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        // Ship a plain-text alternative alongside the HTML: multipart mail is
        // less likely to be flagged promotional / spam than HTML-only.
        return new Content(
            view: 'emails.owner-broadcast',
            text: 'emails.owner-broadcast-text',
        );
    }
}
