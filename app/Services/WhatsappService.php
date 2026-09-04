<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentConfirmation;
use App\Models\BookingPolicy;
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
        $digits = preg_replace('/\D+/', '', $phone);

        foreach (config('booksy.sms.countries', ['963']) as $cc) {
            if ($cc !== '' && str_starts_with($digits, $cc)) {
                return 'sms';
            }
        }

        return 'whatsapp';
    }

    public function send(string $phone, string $message, ?int $companyId = null, ?int $appointmentId = null, string $type = 'general', ?string $channel = null): bool
    {
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
                [$ok, $error] = $this->dispatchViaSms($phone, $message);
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

    /** Effective booking policy for an appointment (unified or per-branch). */
    private function policyFor(Appointment $appointment): BookingPolicy
    {
        $company = $appointment->company ?? Company::find($appointment->company_id);

        return $company
            ? $company->effectiveBookingPolicy($appointment->branch)
            : new BookingPolicy(BookingPolicy::defaults());
    }

    /** Placeholder values shared by every appointment template. */
    private function templateVars(Appointment $appointment, string $link = ''): array
    {
        return [
            'name'     => $appointment->customer?->name ?: ($appointment->customer_name ?? ''),
            'service'  => $appointment->service?->localizedName() ?? $appointment->service?->name ?? '',
            'branch'   => $appointment->branch?->localizedName() ?? '',
            'date'     => $appointment->start_time->translatedFormat('l d M Y'),
            'short_date'=> $this->shortDateTime($appointment->start_time),
            'time'     => $this->timeLabel($appointment->start_time),
            'employee' => ($appointment->employee_requested && $appointment->employee)
                ? $appointment->employee->localizedName() : '',
            'link'     => $link,
        ];
    }

    /**
     * Professional 12-hour clock with correct AM/PM and no leading zero, e.g.
     * "4:00 PM". Used everywhere a time is shown to the customer.
     */
    private function timeLabel(\Illuminate\Support\Carbon $dt): string
    {
        return $dt->format('g:i A');
    }

    /** Compact date + time, e.g. "23/08 — 4:00 PM". */
    private function shortDateTime(\Illuminate\Support\Carbon $dt): string
    {
        return $dt->format('d/m') . ' — ' . $dt->format('g:i A');
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

        $policy = $this->policyFor($appointment);

        // Owner switched the "on booking" reminder off → don't message.
        if (! $policy->reminder_on_booking) return false;

        // Whole visit as one message; one confirmation token acts on every row.
        $visit        = $this->visitAppointments($appointment);
        $primary      = $visit->first();

        // The new SMS system owns confirmation for this branch/number → skip the
        // legacy SMS so the customer doesn't get two copies.
        if ($this->smsSystemOwns($primary, 'confirmation')) return false;

        $confirmation = AppointmentConfirmation::activeFor($primary);
        $confirmUrl   = route('appointment.confirm', ['token' => $confirmation->token]);
        $cancelUrl    = route('appointment.cancel-form', ['token' => $confirmation->token]);

        // A single booking still honours the owner's custom template; grouped
        // visits always use the built-in consolidated layout.
        $message = null;
        if ($visit->count() === 1) {
            $message = $policy->message('msg_confirm', $this->templateVars($primary, $confirmUrl));
        }
        if ($message === null) {
            $message = $this->defaultBookedMessage($visit, $policy, $confirmUrl, $cancelUrl);
        }

        return $this->send(
            $phone, $message,
            $primary->company_id,
            $primary->id,
            'appointment_booked',
            $this->channelFor($phone)   // Syrian → SMS, everyone else → WhatsApp
        );
    }

    /** Consolidated "booked" message: one branch, one date, every service line. */
    private function defaultBookedMessage($visit, BookingPolicy $policy, string $confirmUrl, string $cancelUrl): string
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

        if ($policy->require_confirmation) {
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
        [$ok, , $error] = app(\App\Services\Sms\RasselClient::class)->send($phone, $message);

        return [$ok, $error];
    }

    /**
     * True when the new credit-tracked SMS system owns this message for this
     * appointment — i.e. the number routes over SMS and the branch has opted the
     * matching automation on. When so, the legacy path skips the SMS send to
     * avoid a duplicate; WhatsApp numbers are never affected.
     */
    private function smsSystemOwns(Appointment $appointment, string $type): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (! $phone || $this->channelFor($phone) !== 'sms') {
            return false;
        }

        try {
            return app(\App\Services\Sms\SmsService::class)->automationHandles($appointment, $type, $phone);
        } catch (\Throwable) {
            return false;
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

        $branch  = $primary->branch?->localizedName() ?? '';
        $dayDate = $primary->start_time->translatedFormat('l') . ' ' . $primary->start_time->format('d/m');

        $lines = [];
        foreach ($visit as $a) {
            $svc  = $a->service?->localizedName() ?? $a->service?->name ?? '';
            $time = $this->timeLabel($a->start_time);
            $emp  = ($a->employee_requested && $a->employee) ? " — 👤 " . $a->employee->localizedName() : '';
            $lines[] = "🕐 {$time} • {$svc}{$emp}";
        }

        $message = "🎉 *تم تأكيد موعدك*\n\n"
            . "📍 *{$branch}*\n"
            . "📅 {$dayDate}\n\n"
            . implode("\n", $lines) . "\n\n"
            . "نراك قريباً! 💛";

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
     * The single actionable reminder, sent ~1h before the visit: confirm or
     * cancel (with a reason). Group bookings send ONE reminder for the whole
     * visit, de-duped per group on the primary row. Routed by country channel.
     */
    public function sendReminder(Appointment $appointment, string $slot = '1h'): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $policy = $this->policyFor($appointment);

        // Reuse the "3h" toggle as the owner's on/off switch for this reminder.
        if (! $policy->reminder_3h) return false;

        $visit   = $this->visitAppointments($appointment);
        $primary = $visit->first();

        // New SMS system owns the reminder for this branch/number → skip legacy SMS.
        if ($this->smsSystemOwns($primary, 'reminder')) return false;

        $type = 'reminder_' . $slot;
        $alreadySent = WhatsappLog::where('appointment_id', $primary->id)
            ->whereIn('type', [$type, 'reminder']) // 'reminder' = legacy single reminder
            ->where('status', 'sent')
            ->exists();
        if ($alreadySent) return false;

        $confirmation = AppointmentConfirmation::activeFor($primary);
        $confirmUrl   = route('appointment.confirm', ['token' => $confirmation->token]);
        $cancelUrl    = route('appointment.cancel-form', ['token' => $confirmation->token]);

        $message = $this->defaultReminderMessage($visit, $confirmUrl, $cancelUrl);

        return $this->send($phone, $message, $primary->company_id, $primary->id, $type, $this->channelFor($phone));
    }

    /** Consolidated 1h reminder with confirm / cancel links for the whole visit. */
    private function defaultReminderMessage($visit, string $confirmUrl, string $cancelUrl): string
    {
        $first  = $visit->first();
        $branch = $first->branch?->localizedName() ?? '';
        $time   = $this->timeLabel($first->start_time);

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

        return "⏰ *تذكير: موعدك بعد ساعة*\n\n"
            . "📍 *{$branch}*\n"
            . "🕐 اليوم الساعة {$time}\n"
            . $companion
            . implode("\n", array_values(array_unique($services))) . "\n\n"
            . "يرجى تأكيد حضورك:\n"
            . "✔ تأكيد الموعد:\n{$confirmUrl}\n\n"
            . "❌ إلغاء الموعد (مع ذكر السبب):\n{$cancelUrl}\n\n"
            . "💛 GlowRez";
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
