<?php

namespace App\Mail;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchDeactivatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public Branch $branch,
    ) {}

    public function envelope(): Envelope
    {
        // Sent from the platform contact address configured in the env (contact_to).
        $from = config('mail.contact_to') ?: config('mail.from.address');

        return new Envelope(
            from: new Address($from, config('app.name')),
            subject: __('Your branch has been deactivated'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.branch-deactivated');
    }
}
