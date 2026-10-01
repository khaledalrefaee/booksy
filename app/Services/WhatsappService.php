<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentConfirmation;
use App\Models\Company;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    private string $driver;
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->driver  = config('booksy.whatsapp.driver', 'local');
        $this->baseUrl = rtrim(config('booksy.whatsapp.url', 'http://127.0.0.1:3001'), '/');
        $this->apiKey  = config('booksy.whatsapp.api_key', 'booksy-wa-secret-2026');
    }

    public function isConnected(): bool
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout(5)
                ->get("{$this->baseUrl}/status");
            return $response->ok() && $response->json('status') === 'connected';
        } catch (\Throwable) {
            return false;
        }
    }

    public function getStatus(): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout(5)
                ->get("{$this->baseUrl}/status");
            return $response->json();
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Pick the delivery channel for a phone number. Syrian numbers (and any
     * dial code listed in config booksy.sms.countries) are delivered over local
     * SMS; every other country is delivered over WhatsApp. This is the single
     * routing rule reused wherever we message a raw phone (OTP, account auth).
     */
    public function channelFor(string $phone): string
    {
        // Complete local numbers ("09…") first, or a Syrian number typed at
        // reception would be misrouted to WhatsApp.
        $digits = \App\Support\PhoneNumber::international($phone);

        foreach (config('booksy.sms.countries', ['963']) as $cc) {
            if ($cc !== '' && str_starts_with($digits, $cc)) {
                return 'sms';
            }
        }

        return 'whatsapp';
    }

    public function send(string $phone, string $message, ?int $companyId = null, ?int $appointmentId = null, string $type = 'general', ?string $channel = null): bool
    {
        // A customer notice about an appointment that routes over SMS goes
        // through the credit-tracked SMS pipeline (the branch's balance, the
        // SMS history page) — never straight to the provider for free.
        if ($channel === 'sms' && $companyId !== null && $appointmentId !== null) {
            return $this->sendAppointmentSms($phone, $message, $appointmentId, $type);
        }

        // Plan gate: skip silently when the company's plan doesn't include WhatsApp
        if ($companyId !== null) {
            $company = \App\Models\Company::find($companyId);
            if ($company && ! $company->hasFeature('whatsapp')) {
                return false;
            }
        }

        $log = WhatsappLog::create([
            'company_id'     => $companyId,
            'appointment_id' => $appointmentId,
            'phone'          => $phone,
            'type'           => $type,
            'message'        => $message,
            'status'         => 'queued',
        ]);

        try {
            if ($channel === 'sms') {
                [$ok, $error, $result] = $this->dispatchViaSms($phone, $message);
                $this->recordPlatformSms($phone, $message, $type, $companyId, $ok, $error, $result);
            } else {
                [$ok, $error] = $this->driver === 'meta'
                    ? $this->dispatchViaMeta($phone, $message)
                    : $this->dispatchViaLocal($phone, $message);
            }

            if ($ok) {
                $log->update(['status' => 'sent', 'sent_at' => now()]);
                return true;
            }

            $log->update(['status' => 'failed', 'error' => $error]);
            return false;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::warning("WhatsApp send failed: {$e->getMessage()}");
            return false;
        }
    }

    /** Appointment notice over SMS → SmsService (credits + sms_messages log). */
    private function sendAppointmentSms(string $phone, string $message, int $appointmentId, string $type): bool
    {
        $appointment = Appointment::with(['company', 'branch'])->find($appointmentId);
        if (! $appointment || ! $appointment->company) {
            return false;
        }

        // One SMS per notice per recipient. A move can happen more than once,
        // so the rescheduled notice is keyed on the new time as well.
        $scope = $type === 'appointment_rescheduled'
            ? $appointment->start_time->format('YmdHi')
            : \App\Support\PhoneNumber::international($phone);

        $sms = app(\App\Services\Sms\SmsService::class)->sendManual(
            $appointment->company,
            $appointment->branch,
            $phone,
            $message,
            [
                'customer_id'    => $appointment->customer_id,
                'appointment_id' => $appointment->id,
                'message_type'   => substr($type, 0, 24),
                'dedupe_key'     => "sms:{$type}:a{$appointment->id}:{$scope}",
            ]
        );

        return $sms?->status === 'queued';
    }

    /** @return array{0: bool, 1: ?string} [ok, error] */
    private function dispatchViaLocal(string $phone, string $message): array
    {
        $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
            ->timeout(30)
            ->post("{$this->baseUrl}/send", [
                'phone'   => $phone,
                'message' => $message,
            ]);

        return [$response->ok(), $response->ok() ? null : $response->body()];
    }

    /**
     * Official WhatsApp Cloud API. Free-form text is only delivered inside an
     * open 24h customer-service window; business-initiated notifications will
     * need approved templates once the account is verified.
     *
     * @return array{0: bool, 1: ?string} [ok, error]
     */
    private function dispatchViaMeta(string $phone, string $message): array
    {
        $token   = config('booksy.whatsapp.meta.token');
        $phoneId = config('booksy.whatsapp.meta.phone_number_id');
        $version = config('booksy.whatsapp.meta.api_version', 'v21.0');

        if (! $token || ! $phoneId) {
            return [false, 'Meta driver selected but WHATSAPP_META_TOKEN / WHATSAPP_META_PHONE_ID are not configured.'];
        }

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => preg_replace('/\D+/', '', $phone),
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        return [$response->ok(), $response->ok() ? null : $response->body()];
    }

    /**
     * Professional 12-hour clock with correct AM/PM and no leading zero, e.g.
     * "4:00 PM". Used everywhere a time is shown to the customer.
     */
    private function timeLabel(\Illuminate\Support\Carbon $dt): string
    {
        return $dt->format('g:i A');
    }

    /**
     * Every appointment of a visit (a multi-service / multi-guest booking shares
     * one booking_group_id). Single bookings return just themselves. Ordered by
     * time so the message reads top-to-bottom.
     */
    private function visitAppointments(Appointment $appointment)
    {
        if (! $appointment->booking_group_id) {
            return collect([$appointment]);
        }

        return Appointment::where('booking_group_id', $appointment->booking_group_id)
            ->with(['service', 'branch', 'employee', 'customer'])
            ->orderBy('start_time')
            ->get();
    }

    public function sendAppointmentBooked(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        // Customer messages have ONE owner: the branch's "Customer messages"
        // settings. SMS numbers are handled by SmsService::confirmation (credit
        // tracked); this path is WhatsApp only.
        $sms = app(\App\Services\Sms\SmsService::class);
        if ($sms->routesOverSms($phone)) return false;

        $settings = $sms->settingsFor($appointment);
        if (! $settings?->confirmation_enabled) return false;

        // Whole visit as one message; one confirmation token acts on every row.
        $visit        = $this->visitAppointments($appointment);
        $primary      = $visit->first();

        $confirmation = AppointmentConfirmation::activeFor($primary);
        $confirmUrl   = route('appointment.confirm', ['token' => $confirmation->token]);
        $cancelUrl    = route('appointment.cancel-form', ['token' => $confirmation->token]);

        // WhatsApp keeps its richer built-in layout (the short owner templates
        // are the SMS texts). The booking message carries no confirm links —
        // those come with the reminder.
        $message = $this->defaultBookedMessage($visit, false, $confirmUrl, $cancelUrl);

        return $this->send($phone, $message, $primary->company_id, $primary->id, 'appointment_booked', 'whatsapp');
    }

    /** Consolidated "booked" message: one branch, one date, every service line. */
    private function defaultBookedMessage($visit, bool $askConfirmation, string $confirmUrl, string $cancelUrl): string
    {
        $first    = $visit->first();
        $branch   = $first->branch?->localizedName() ?? '';
        $dayDate  = $first->start_time->translatedFormat('l') . ' ' . $first->start_time->format('d/m');

        // Extra guests carry a label on their rows; guest 0 (the account holder)
        // does not. This lets the message state, unambiguously, whether the visit
        // includes a companion.
        $guestLabels = $visit->pluck('customer_name')->filter()->unique()->values();
        $companion = $guestLabels->isNotEmpty()
            ? "👥 لك و" . $guestLabels->count() . ($guestLabels->count() === 1 ? ' ضيف' : ' ضيوف')
            : "👤 الحجز لك وحدك — بدون ضيوف";

        $lines = [];
        foreach ($visit as $a) {
            $svc   = $a->service?->localizedName() ?? $a->service?->name ?? '';
            $time  = $this->timeLabel($a->start_time);
            $emp   = ($a->employee_requested && $a->employee) ? " — 👤 " . $a->employee->localizedName() : '';
            $guest = filled($a->customer_name) ? " — 🧑‍🤝‍🧑 {$a->customer_name}" : '';
            $lines[] = "🕐 {$time} • {$svc}{$emp}{$guest}";
        }

        $msg = "✅ *تم حجز موعدك بنجاح*\n\n"
            . "📍 *{$branch}*\n"
            . "📅 {$dayDate}\n"
            . "{$companion}\n\n"
            . implode("\n", $lines) . "\n\n";

        if ($visit->count() > 1) {
            $msg .= "💰 الإجمالي: " . (int) $visit->sum('total_price') . "\n";
        }
        if ($first->reference) {
            $msg .= "🔖 رقم الحجز: {$first->reference}\n";
        }
        $msg .= "\n";

        if ($askConfirmation) {
            $msg .= "✔ لتأكيد الموعد:\n{$confirmUrl}\n\n"
                . "❌ لإلغاء الموعد:\n{$cancelUrl}\n\n";
        }

        return $msg . "نتطلّع لرؤيتك! 💛";
    }

    /**
     * SMS channel. Delegates to the single Rassel client so there is one code
     * path to the provider (shared with the credit-tracked SMS system). Inert
     * until config('booksy.sms') is filled in — returns a clear reason so
     * nothing silently disappears.
     *
     * @return array{0: bool, 1: ?string} [ok, error]
     */
    private function dispatchViaSms(string $phone, string $message): array
    {
        $result = app(\App\Services\Sms\RasselClient::class)->send($phone, $message);

        return [(bool) ($result['ok'] ?? false), $result['error'] ?? null, $result];
    }

    /**
     * Verification codes and other platform SMS go straight to the provider (no
     * company wallet), yet the owner's SMS log must still show them — delivered
     * or failed — so each one is recorded with no credits charged.
     */
    private function recordPlatformSms(string $phone, string $message, string $type, ?int $companyId, bool $ok, ?string $error, array $result): void
    {
        try {
            $analysis = \App\Services\Sms\SmsSegment::analyze($message);

            \App\Models\SmsMessage::create([
                'company_id'          => $companyId,
                'message_type'        => substr($type, 0, 24),
                'phone'               => \App\Support\PhoneNumber::international($phone),
                'body'                => $message,
                'segments'            => $analysis['segments'],
                'credits_used'        => 0,
                'status'              => $ok ? (($result['provider_status'] ?? 'sent') === 'queued' ? 'queued' : 'sent') : 'failed',
                'provider'            => 'rasel',
                'provider_status'     => $result['provider_status'] ?? null,
                'provider_message_id' => $result['message_id'] ?? null,
                'request_id'          => $result['request_id'] ?? null,
                'usage_id'            => $result['usage_id'] ?? null,
                'queue_id'            => $result['queue_id'] ?? null,
                'resolved_provider'   => $result['resolved_provider'] ?? null,
                'sender_source'       => $result['sender_source'] ?? null,
                'estimated_cost'      => $result['estimated_cost'] ?? null,
                'cost_currency'       => $result['currency'] ?? null,
                'error_code'          => $result['code'] ?? null,
                'failure_reason'      => $ok ? null : \Illuminate\Support\Str::limit((string) $error, 500),
                'sent_at'             => $ok ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Could not record platform SMS: {$e->getMessage()}");
        }
    }

    public function sendAppointmentConfirmed(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        // One message per visit, even when every grouped row flips to confirmed.
        $visit   = $this->visitAppointments($appointment);
        $primary = $visit->first();
        if ($this->alreadySent($primary->id, 'appointment_confirmed')) return false;

        // The owner's short "approval" template (SMS and WhatsApp alike); a
        // multi-service visit is described by its first row's date and time.
        $sms = app(\App\Services\Sms\SmsService::class);
        $primary->loadMissing(['branch', 'service', 'customer']);
        $tpl = $sms->resolveTemplate($primary->company_id, $primary->branch_id, 'approval');
        $message = $sms->render($tpl->body ?? \App\Models\SmsTemplate::defaultBody('approval', app()->getLocale() === 'en' ? 'en' : 'ar'), $primary);
        if (trim($message) === '') return false;

        return $this->send($phone, $message, $primary->company_id, $primary->id, 'appointment_confirmed', $this->channelFor($phone));
    }

    public function sendAppointmentCancelled(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $visit   = $this->visitAppointments($appointment);
        $primary = $visit->first();
        if ($this->alreadySent($primary->id, 'appointment_cancelled')) return false;

        $branch = $primary->branch?->localizedName() ?? '';
        $reason = $this->cancellationReason($primary);

        $message = "⚠️ *تم إلغاء موعدك*\n\n"
            . "📍 *{$branch}*\n"
            . "📅 {$primary->start_time->translatedFormat('l')} {$primary->start_time->format('d/m')} — ⏰ {$this->timeLabel($primary->start_time)}\n"
            . ($reason ? "📝 السبب: {$reason}\n" : '')
            . "\nيمكنك حجز موعد جديد في أي وقت 🙏";

        return $this->send($phone, $message, $primary->company_id, $primary->id, 'appointment_cancelled', $this->channelFor($phone));
    }

    /** Single "your appointment moved" message for the whole visit. */
    public function sendAppointmentRescheduled(Appointment $appointment, string $oldStartIso): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $visit   = $this->visitAppointments($appointment);
        $primary = $visit->first();
        $branch  = $primary->branch?->localizedName() ?? '';
        $old     = \Illuminate\Support\Carbon::parse($oldStartIso);

        $lines = [];
        foreach ($visit as $a) {
            $svc = $a->service?->localizedName() ?? $a->service?->name ?? '';
            $emp = ($a->employee_requested && $a->employee) ? " — 👤 " . $a->employee->localizedName() : '';
            $lines[] = "🕐 {$this->timeLabel($a->start_time)} • {$svc}{$emp}";
        }

        $message = "🔄 *تم تغيير موعدك بنجاح*\n\n"
            . "📍 *{$branch}*\n"
            . "❌ السابق: {$old->translatedFormat('l')} {$old->format('d/m')} — {$this->timeLabel($old)}\n"
            . "✅ الجديد: {$primary->start_time->translatedFormat('l')} {$primary->start_time->format('d/m')} — {$this->timeLabel($primary->start_time)}\n\n"
            . implode("\n", $lines) . "\n"
            . ($primary->reference ? "🔖 رقم الحجز: {$primary->reference}\n" : '')
            . "\nنراك في موعدك الجديد! 💛";

        return $this->send($phone, $message, $primary->company_id, $primary->id, 'appointment_rescheduled', $this->channelFor($phone));
    }

    /**
     * Venue rejected a still-pending request — commercially different from a
     * cancellation, so it gets its own message: the reason + a link to pick
     * another time, instead of a bare "cancelled".
     */
    public function sendAppointmentRejected(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $visit   = $this->visitAppointments($appointment);
        $primary = $visit->first();
        if ($this->alreadySent($primary->id, 'appointment_rejected')) return false;

        $branch = $primary->branch?->localizedName() ?? '';
        $reason = $this->cancellationReason($primary);
        $rebook = $primary->branch ? route('front.branch', $primary->branch) : url('/');

        $message = "❌ *تعذّر تأكيد موعدك*\n\n"
            . "📍 *{$branch}*\n"
            . "📅 {$primary->start_time->translatedFormat('l')} {$primary->start_time->format('d/m')} — ⏰ {$this->timeLabel($primary->start_time)}\n"
            . ($reason ? "📝 السبب: {$reason}\n" : '')
            . "\nنعتذر عن ذلك 🙏 يمكنك اختيار موعد آخر:\n{$rebook}";

        return $this->send($phone, $message, $primary->company_id, $primary->id, 'appointment_rejected', $this->channelFor($phone));
    }

    /** A slot the customer was waiting for just opened — nudge them to grab it. */
    public function sendWaitlistOpening(\App\Models\BookingWaitlistEntry $entry, Appointment $freed): bool
    {
        $phone = $entry->customer?->phone;
        if (!$phone) return false;

        $branch = $freed->branch?->localizedName() ?? $entry->branch?->localizedName() ?? '';
        $svc    = $freed->service?->localizedName() ?? $entry->service?->localizedName() ?? '';
        $book   = $freed->branch ? route('front.branch', $freed->branch) : url('/');

        $message = "🎉 *صار في موعد فاضي!*\n\n"
            . "📍 *{$branch}*\n"
            . ($svc ? "💇 {$svc}\n" : '')
            . "📅 {$freed->start_time->translatedFormat('l')} {$freed->start_time->format('d/m')} — ⏰ {$this->timeLabel($freed->start_time)}\n\n"
            . "سارِع بالحجز قبل أن يحجزه غيرك 👇\n{$book}";

        return $this->send($phone, $message, $freed->company_id, $freed->id, 'waitlist_opening', $this->channelFor($phone));
    }

    /** True when a message of this type was already sent for the primary row. */
    private function alreadySent(int $appointmentId, string $type): bool
    {
        return WhatsappLog::where('appointment_id', $appointmentId)
            ->where('type', $type)
            ->where('status', 'sent')
            ->exists();
    }

    /** The reason recorded on the latest cancellation transition, if any. */
    private function cancellationReason(Appointment $appointment): ?string
    {
        $t = $appointment->transitions()
            ->whereIn('to_status', ['cancelled_by_customer', 'cancelled_by_salon'])
            ->latest('id')
            ->first();

        return $t?->reason ?: ($appointment->rejection_reason ?: null);
    }

    /**
     * WhatsApp reminder before the visit, at the branch's chosen lead time
     * ("Customer messages"). Group bookings send ONE reminder for the whole
     * visit. De-duped per visit + start time, so a moved booking is reminded
     * again for its new time. SMS numbers are handled by SmsService::reminder.
     */
    public function sendReminder(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $sms = app(\App\Services\Sms\SmsService::class);
        if ($sms->routesOverSms($phone)) return false;

        $settings = $sms->settingsFor($appointment);
        if (! $settings?->reminder_enabled) return false;

        $visit   = $this->visitAppointments($appointment);
        $primary = $visit->first();

        $type = 'reminder_' . $primary->start_time->format('YmdHi');
        $alreadySent = WhatsappLog::where('appointment_id', $primary->id)
            ->where('type', $type)
            ->where('status', 'sent')
            ->exists();
        if ($alreadySent) return false;

        $confirmation = AppointmentConfirmation::activeFor($primary);
        $confirmUrl   = route('appointment.c', ['token' => $confirmation->token]);
        $cancelUrl    = route('appointment.x', ['token' => $confirmation->token]);

        // The reminder always offers confirm / cancel.
        $message = $this->defaultReminderMessage($visit, true, $confirmUrl, $cancelUrl);

        return $this->send($phone, $message, $primary->company_id, $primary->id, $type, 'whatsapp');
    }

    /** WhatsApp follow-up after the last visit (SMS numbers: SmsService::followup). */
    public function sendFollowup(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $sms = app(\App\Services\Sms\SmsService::class);
        if ($sms->routesOverSms($phone)) return false;
        if (! $sms->settingsFor($appointment)?->followup_enabled) return false;

        $type = 'followup';
        if ($this->alreadySent($appointment->id, $type)) return false;

        $tpl  = $sms->resolveTemplate($appointment->company_id, $appointment->branch_id, 'followup');
        $body = $sms->render($tpl->body ?? \App\Models\SmsTemplate::defaultBody('followup'), $appointment);
        if (trim($body) === '') return false;

        return $this->send($phone, $body, $appointment->company_id, $appointment->id, $type, 'whatsapp');
    }

    /** Consolidated reminder for the whole visit, with confirm / cancel links when asked. */
    private function defaultReminderMessage($visit, bool $askConfirmation, string $confirmUrl, string $cancelUrl): string
    {
        $first  = $visit->first();
        $branch = $first->branch?->localizedName() ?? '';
        $time   = $this->timeLabel($first->start_time);
        $isToday = $first->start_time->isSameDay($first->branch?->localNow() ?? now());
        $when    = $isToday
            ? "اليوم الساعة {$time}"
            : $first->start_time->translatedFormat('l d/m') . " الساعة {$time}";

        $guestLabels = $visit->pluck('customer_name')->filter()->unique()->values();
        $companion   = $guestLabels->isNotEmpty()
            ? "👥 لك و" . $guestLabels->count() . ($guestLabels->count() === 1 ? ' ضيف' : ' ضيوف') . "\n"
            : '';

        $services = [];
        foreach ($visit as $a) {
            $svc = $a->service?->localizedName() ?? $a->service?->name ?? '';
            $emp = ($a->employee_requested && $a->employee) ? " — 👤 " . $a->employee->localizedName() : '';
            $services[] = "💇 {$svc}{$emp}";
        }

        // The lead time is the branch's choice now, so the header states the
        // day and time instead of a fixed "in one hour".
        $msg = "⏰ *تذكير بموعدك*\n\n"
            . "📍 *{$branch}*\n"
            . "🕐 {$when}\n"
            . $companion
            . implode("\n", array_values(array_unique($services))) . "\n\n";

        if ($askConfirmation) {
            $msg .= "يرجى تأكيد حضورك:\n"
                . "✔ تأكيد الموعد:\n{$confirmUrl}\n\n"
                . "❌ إلغاء الموعد (مع ذكر السبب):\n{$cancelUrl}\n\n";
        } else {
            $msg .= "بانتظارك! ";
        }

        return $msg . "💛 GlowRez";
    }

    // ── Employee notifications ───────────────────────────────────────────────

    /**
     * Payslip summary sent to the employee right after a salary payment.
     *
     * @param array{period: string, base: float, commissions: float, deductions: float, net: float, currency: string, method: string} $data
     */
    public function sendPayslip(\App\Models\Employee $employee, array $data): bool
    {
        if (! $employee->phone) return false;

        $lines = [
            "💰 *تم صرف راتبك*",
            "",
            "👤 {$employee->localizedName()}",
            "📅 الفترة: {$data['period']}",
            "",
            "الراتب الأساسي: " . number_format($data['base'], 0) . " {$data['currency']}",
        ];

        if ($data['commissions'] > 0) {
            $lines[] = "العمولات: +" . number_format($data['commissions'], 0) . " {$data['currency']}";
        }
        if ($data['deductions'] > 0) {
            $lines[] = "الخصومات: -" . number_format($data['deductions'], 0) . " {$data['currency']}";
        }

        $lines[] = "";
        $lines[] = "✅ *الصافي: " . number_format($data['net'], 0) . " {$data['currency']}*";
        $lines[] = "طريقة الدفع: {$data['method']}";

        return $this->send($employee->phone, implode("\n", $lines), $employee->company_id, null, 'payslip');
    }

    /** Approval / rejection notice sent to the employee after a leave decision. */
    public function sendLeaveDecision(\App\Models\EmployeeLeave $leave, ?int $remainingBalance = null): bool
    {
        $employee = $leave->employee;
        if (! $employee?->phone) return false;

        $typeMeta  = $leave->typeMeta();
        $typeLabel = __($typeMeta['label_key']);
        $from      = $leave->start_date->translatedFormat('D d M Y');
        $to        = $leave->end_date->translatedFormat('D d M Y');
        $days      = $leave->daysCount();

        if ($leave->status === 'approved') {
            $message = "✅ *تمت الموافقة على إجازتك*\n\n"
                . "{$typeMeta['icon']} {$typeLabel}\n"
                . "📅 من {$from}\n"
                . "📅 إلى {$to}\n"
                . "⏳ المدة: {$days} يوم";
            if ($leave->type === 'annual' && $remainingBalance !== null) {
                $message .= "\n\n🏖️ رصيدك السنوي المتبقي: {$remainingBalance} يوم";
            }
        } else {
            $message = "❌ *نعتذر — تم رفض طلب إجازتك*\n\n"
                . "{$typeMeta['icon']} {$typeLabel}\n"
                . "📅 من {$from} إلى {$to}";
            if ($leave->notes) {
                $message .= "\n📝 السبب: {$leave->notes}";
            }
            $message .= "\n\nيمكنك مراجعة الإدارة للتفاصيل.";
        }

        return $this->send($employee->phone, $message, $employee->company_id, null, 'leave_decision');
    }

    /** License-expiry reminder sent to the employee (and optionally to the company). */
    public function sendLicenseExpiryReminder(\App\Models\Employee $employee, int $daysLeft): bool
    {
        if (! $employee->phone) return false;

        $expiry = $employee->license_expiry->translatedFormat('d M Y');
        $when   = $daysLeft <= 0 ? 'اليوم' : "بعد {$daysLeft} يوم";

        $message = "📜 *تذكير بانتهاء رخصة المزاولة*\n\n"
            . "👤 {$employee->localizedName()}\n"
            . "🔢 رقم الرخصة: " . ($employee->license_number ?: '—') . "\n"
            . "⚠️ تنتهي {$when} — بتاريخ {$expiry}\n\n"
            . "يرجى تجديدها وتحديث بياناتها في النظام.";

        return $this->send($employee->phone, $message, $employee->company_id, null, 'license_reminder');
    }
}
