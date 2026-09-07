<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The 4-digit account-verification code sent to a company owner after
 * registration. Branded HTML; falls back gracefully in text-only clients.
 */
class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ownerName,
        public string $code,
        public int $minutes,
        public bool $isAr,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isAr
                ? 'رمز تأكيد الحساب — GlowRez'
                : 'Account verification code — GlowRez',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code');
    }
}
