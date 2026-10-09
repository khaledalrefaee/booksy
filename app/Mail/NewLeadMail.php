<?php

namespace App\Mail;

use App\Models\Lead;
use App\Support\LeadCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "New GlowRez Lead — Beauty center — Damascus": the instant alert to the team
 * inbox (config('leads.notification_email')) when someone submits /join. Carries
 * every field the team needs to call back, plus a button straight to the lead in
 * the Owner dashboard. Sent through the app's normal mailer (Resend) and queue.
 */
class NewLeadMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $leadId, public bool $repeat = false)
    {
    }

    public function envelope(): Envelope
    {
        $lead = Lead::findOrFail($this->leadId);

        // Subject is English on purpose (inbox-scannable, matches the agreed format);
        // the body below follows config('leads.email_locale').
        $subject = sprintf(
            '%s — %s — %s',
            $this->repeat ? 'Returning GlowRez Lead' : 'New GlowRez Lead',
            LeadCatalog::label('business_types', $lead->business_type, 'en'),
            LeadCatalog::label('cities', $lead->city, 'en'),
        );

        return new Envelope(
            subject: $subject,
            replyTo: $lead->email && filter_var($lead->email, FILTER_VALIDATE_EMAIL)
                ? [new Address($lead->email, $lead->full_name)]
                : [],
        );
    }

    public function content(): Content
    {
        $lead   = Lead::findOrFail($this->leadId);
        $locale = config('leads.email_locale', 'ar') === 'en' ? 'en' : 'ar';

        return new Content(
            view: 'emails.new-lead',
            text: 'emails.new-lead-text',
            with: [
                'lead'      => $lead,
                'repeat'    => $this->repeat,
                'isAr'      => $locale === 'ar',
                'locale'    => $locale,
                'openUrl'   => route('owner.leads.show', $lead),
                'rows'      => $this->rows($lead, $locale),
            ],
        );
    }

    /** Label ⇒ value pairs in the order they appear in the email (and its text twin). */
    private function rows(Lead $lead, string $locale): array
    {
        $ar = $locale === 'ar';
        $t  = fn (string $a, string $e) => $ar ? $a : $e;
        $dash = '—';

        return [
            $t('الاسم', 'Name')                 => $lead->full_name,
            $t('رقم الهاتف', 'Phone')           => $lead->phone,
            'WhatsApp'                           => $lead->whatsapp ?: $dash,
            $t('البريد', 'Email')               => $lead->email ?: $dash,
            $t('اسم المنشأة', 'Business')       => $lead->business_name,
            $t('نوع المنشأة', 'Business type')  => LeadCatalog::label('business_types', $lead->business_type, $locale),
            $t('المحافظة', 'City')              => LeadCatalog::label('cities', $lead->city, $locale),
            $t('المنطقة', 'Area')               => $lead->area ?: $dash,
            $t('عدد الفروع', 'Branches')        => (string) $lead->number_of_branches,
            $t('الاهتمامات', 'Interests')       => implode($ar ? '، ' : ', ', LeadCatalog::interestLabels($lead->interests, $locale)) ?: $dash,
            $t('المصدر', 'Source')              => LeadCatalog::label('sources', $lead->source, $locale),
            $t('الحملة', 'Campaign')            => $lead->campaign ?: $dash,
            $t('وقت التسجيل', 'Submitted at')   => $lead->last_interaction_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? $dash,
        ];
    }
}
