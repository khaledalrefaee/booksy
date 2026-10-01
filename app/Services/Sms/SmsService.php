<?php

namespace App\Services\Sms;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\SmsAutomationSetting;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Jobs\SendSmsJob;
use Illuminate\Support\Carbon;

/**
 * The automation brain. Turns an appointment event into a rendered, credit-aware
 * SMS: resolves the branch's automation settings and template, fills variables,
 * costs the body in credits, records an sms_messages row (deduped so a repeated
 * job can't double-send), and hands delivery to SendSmsJob on the queue so the
 * booking request never waits on Rassel.
 *
 * Only fires for SMS-channel (e.g. Syrian) numbers; WhatsApp numbers stay on the
 * existing WhatsappService path.
 */
class SmsService
{
    public function __construct(private SmsCreditService $credits) {}

    // ── Public automation entry points ───────────────────────────────────────

    public function confirmation(Appointment $appointment): ?SmsMessage
    {
        return $this->queueForAppointment($appointment, 'confirmation');
    }

    public function reminder(Appointment $appointment): ?SmsMessage
    {
        return $this->queueForAppointment($appointment, 'reminder');
    }

    public function followup(Appointment $appointment): ?SmsMessage
    {
        return $this->queueForAppointment($appointment, 'followup');
    }

    /**
     * The single routing rule for customer messages: local-network numbers
     * (after completing "09…" with the country code) go over SMS through this
     * service and the branch's credits; everything else goes over WhatsApp.
     */
    public function routesOverSms(?string $phone): bool
    {
        return $phone ? $this->isSmsChannel($this->internationalize($phone)) : false;
    }

    /** The branch's customer-message settings (defaults when never saved). */
    public function settingsFor(Appointment $appointment): ?SmsAutomationSetting
    {
        return $this->settingFor($appointment->company_id, $appointment->branch_id);
    }

    // ── Core pipeline ────────────────────────────────────────────────────────

    private function queueForAppointment(Appointment $appointment, string $type): ?SmsMessage
    {
        $phone = $this->phoneFor($appointment);
        if (! $phone || ! $this->isSmsChannel($phone)) {
            return null;
        }

        $setting = $this->settingFor($appointment->company_id, $appointment->branch_id);
        if (! $setting || ! $setting->enabledFor($type)) {
            return null;
        }

        $dedupeKey = $this->dedupeKey($appointment, $type);
        if (SmsMessage::where('dedupe_key', $dedupeKey)->exists()) {
            return null; // already handled this logical message
        }

        $template = $this->resolveTemplate($appointment->company_id, $appointment->branch_id, $type);
        $raw      = $template->body ?? SmsTemplate::defaultBody($type, $this->localeFor($appointment->company_id));

        $body = $this->render($raw, $appointment);
        if (trim($body) === '') {
            return null;
        }

        $branch  = $appointment->branch instanceof Branch ? $appointment->branch : Branch::find($appointment->branch_id);
        $credits = SmsSegment::credits($body);
        $wallet  = $branch ? $this->credits->contextWalletFor($branch) : null;

        $message = new SmsMessage([
            'company_id'     => $appointment->company_id,
            'branch_id'      => $appointment->branch_id,
            'customer_id'    => $appointment->customer_id,
            'appointment_id' => $appointment->id,
            'template_id'    => $template?->id,
            'wallet_id'      => $wallet?->id,
            'message_type'   => $type,
            'phone'          => $phone,
            'body'           => $body,
            'segments'       => SmsSegment::analyze($body)['segments'],
            'credits_used'   => 0,
            'provider'       => 'rasel',
            'dedupe_key'     => $dedupeKey,
        ]);

        // No wallet or no credits at all → record the intent as skipped, don't queue.
        if (! $wallet || ! $wallet->hasCredits($credits)) {
            $message->status         = 'skipped';
            $message->failure_reason = 'insufficient_credits';
            $message->save();

            return $message;
        }

        $message->status = 'queued';
        $message->save();

        SendSmsJob::dispatch($message->id, $credits);

        return $message;
    }

    /**
     * Send any other SMS through the same credit pipeline: the always-on
     * appointment notices (confirmed / moved / cancelled / waitlist) and
     * manual sends. meta: customer_id, appointment_id, message_type, dedupe_key.
     */
    public function sendManual(Company $company, ?Branch $branch, string $phone, string $body, array $meta = []): ?SmsMessage
    {
        if (trim($body) === '' || ! $this->routesOverSms($phone)) {
            return null;
        }
        if (! empty($meta['dedupe_key']) && SmsMessage::where('dedupe_key', $meta['dedupe_key'])->exists()) {
            return null; // this exact notice was already handled
        }

        $credits = SmsSegment::credits($body);
        $wallet  = $branch ? $this->credits->contextWalletFor($branch) : $company->smsPoolWallet()->first();

        $message = new SmsMessage([
            'company_id'     => $company->id,
            'branch_id'      => $branch?->id,
            'customer_id'    => $meta['customer_id'] ?? null,
            'appointment_id' => $meta['appointment_id'] ?? null,
            'wallet_id'      => $wallet?->id,
            'message_type'   => $meta['message_type'] ?? 'manual',
            'phone'          => $this->internationalize($phone),
            'body'         => $body,
            'segments'     => SmsSegment::analyze($body)['segments'],
            'provider'     => 'rasel',
            'dedupe_key'   => $meta['dedupe_key'] ?? null,
        ]);

        if (! $wallet || ! $wallet->hasCredits($credits)) {
            $message->status         = 'skipped';
            $message->failure_reason = 'insufficient_credits';
            $message->save();

            return $message;
        }

        $message->status = 'queued';
        $message->save();

        SendSmsJob::dispatch($message->id, $credits);

        return $message;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function settingFor(int $companyId, ?int $branchId): ?SmsAutomationSetting
    {
        if (! $branchId) {
            return null;
        }

        return SmsAutomationSetting::forBranch($companyId, $branchId);
    }

    /**
     * The owner's system template for this message, in the system language
     * (Arabic when there is no row in it). Templates are owner-managed only.
     */
    public function resolveTemplate(int $companyId, ?int $branchId, string $type, ?string $locale = null): ?SmsTemplate
    {
        $locale ??= $this->localeFor($companyId);

        $candidates = SmsTemplate::whereNull('company_id')->whereNull('branch_id')
            ->where('key', $type)->where('is_active', true)
            ->whereIn('locale', [$locale, 'ar'])
            ->get();

        return $candidates->firstWhere('locale', $locale) ?? $candidates->first();
    }

    public function render(string $body, Appointment $appointment): string
    {
        $vars = $this->variablesFor($appointment);

        // Confirm / cancel links only when the text asks for them — minting a
        // token for every message would be wasted work. One token per visit
        // (the group's first row), shared with every other message of it.
        if (str_contains($body, 'confirm_link') || str_contains($body, 'cancel_link')) {
            $primary = $appointment->booking_group_id
                ? (Appointment::where('booking_group_id', $appointment->booking_group_id)->orderBy('start_time')->first() ?? $appointment)
                : $appointment;
            $token = \App\Models\AppointmentConfirmation::activeFor($primary)->token;
            $vars['confirm_link'] = route('appointment.c', ['token' => $token]);
            $vars['cancel_link']  = route('appointment.x', ['token' => $token]);
        }

        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($vars) {
            return $vars[$m[1]] ?? '';
        }, $body);
    }

    /** The values behind the template {{variables}}. */
    public function variablesFor(Appointment $appointment): array
    {
        $start = $appointment->start_time instanceof Carbon
            ? $appointment->start_time
            : Carbon::parse($appointment->start_time);

        return [
            'customer_name'    => $appointment->customer?->name ?: ($appointment->customer_name ?? ''),
            'branch_name'      => $appointment->branch?->localizedName() ?? '',
            'service_name'     => $appointment->service?->localizedName() ?? $appointment->service?->name ?? '',
            'appointment_date' => $start->translatedFormat('l d/m'),
            'appointment_time' => $start->format('g:i A'),
        ];
    }

    private function phoneFor(Appointment $appointment): ?string
    {
        $phone = $appointment->customer_phone ?: $appointment->customer?->phone;

        return $phone ? $this->internationalize($phone) : null;
    }

    private function internationalize(string $phone): string
    {
        return \App\Support\PhoneNumber::international($phone);
    }

    /**
     * One logical message per booking visit (grouped rows share the key).
     * A reminder is also tied to the start time it announced: move the booking
     * and the new time gets its own reminder, while the old one can't repeat.
     */
    private function dedupeKey(Appointment $appointment, string $type): string
    {
        $scope = $appointment->booking_group_id
            ? 'g' . $appointment->booking_group_id
            : 'a' . $appointment->id;

        if ($type === 'reminder') {
            // A group's guests start one after another — key on the visit's
            // first start so a later guest can't trigger a second reminder.
            $start = $appointment->booking_group_id
                ? Appointment::where('booking_group_id', $appointment->booking_group_id)->min('start_time')
                : $appointment->start_time;
            $start = $start instanceof Carbon ? $start : Carbon::parse($start);
            $scope .= ':' . $start->format('YmdHi');
        }

        return "sms:{$type}:{$scope}";
    }

    /** Syrian (and any configured dial code) numbers are SMS; everyone else WhatsApp. */
    private function isSmsChannel(string $phone): bool
    {
        $digits = preg_replace('/\D+/', '', $phone);

        foreach (config('booksy.sms.countries', ['963']) as $cc) {
            if ($cc !== '' && str_starts_with($digits, (string) $cc)) {
                return true;
            }
        }

        return false;
    }

    private function localeFor(int $companyId): string
    {
        return app()->getLocale() === 'en' ? 'en' : 'ar';
    }
}
