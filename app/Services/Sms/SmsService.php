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

    /** Does the branch's opt-in cover SMS for this event on an SMS-channel phone? */
    public function automationHandles(Appointment $appointment, string $type, ?string $phone = null): bool
    {
        $phone ??= $this->phoneFor($appointment);
        if (! $phone || ! $this->isSmsChannel($phone)) {
            return false;
        }

        $setting = $this->settingFor($appointment->company_id, $appointment->branch_id);

        return $setting?->enabledFor($type) ?? false;
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
        $body     = $this->render($template->body ?? SmsTemplate::defaultBody($type), $appointment);
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
     * Send an ad-hoc (non-appointment) SMS through the same credit pipeline.
     * Used by manual sends / future campaigns.
     */
    public function sendManual(Company $company, ?Branch $branch, string $phone, string $body, array $meta = []): ?SmsMessage
    {
        if (trim($body) === '' || ! $this->isSmsChannel($phone)) {
            return null;
        }

        $credits = SmsSegment::credits($body);
        $wallet  = $branch ? $this->credits->contextWalletFor($branch) : $company->smsPoolWallet()->first();

        $message = new SmsMessage([
            'company_id'   => $company->id,
            'branch_id'    => $branch?->id,
            'customer_id'  => $meta['customer_id'] ?? null,
            'wallet_id'    => $wallet?->id,
            'message_type' => 'manual',
            'phone'        => $phone,
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

        return SmsAutomationSetting::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->first();
    }

    /** Most-specific-wins: branch → company → system default row. */
    public function resolveTemplate(int $companyId, ?int $branchId, string $type, ?string $locale = null): ?SmsTemplate
    {
        $locale ??= $this->localeFor($companyId);

        $query = SmsTemplate::where('key', $type)->where('is_active', true)
            ->where(function ($q) use ($companyId, $branchId, $locale) {
                $q->where('locale', $locale)->orWhere('locale', 'ar');
            });

        $candidates = $query->get();

        return $candidates->first(fn ($t) => $t->company_id === $companyId && $t->branch_id === $branchId)
            ?? $candidates->first(fn ($t) => $t->company_id === $companyId && $t->branch_id === null)
            ?? $candidates->first(fn ($t) => $t->company_id === null)
            ?? null;
    }

    public function render(string $body, Appointment $appointment): string
    {
        $vars = $this->variablesFor($appointment);

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
            'appointment_date' => $start->translatedFormat('l d M Y'),
            'appointment_time' => $start->format('g:i A'),
        ];
    }

    private function phoneFor(Appointment $appointment): ?string
    {
        return $appointment->customer_phone ?: $appointment->customer?->phone;
    }

    /** One logical message per booking visit (grouped rows share the key). */
    private function dedupeKey(Appointment $appointment, string $type): string
    {
        $scope = $appointment->booking_group_id
            ? 'g' . $appointment->booking_group_id
            : 'a' . $appointment->id;

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
