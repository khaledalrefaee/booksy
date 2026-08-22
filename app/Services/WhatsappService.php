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
            'name'    => $appointment->customer_name ?? $appointment->customer?->name ?? '',
            'service' => $appointment->service?->localizedName() ?? $appointment->service?->name ?? '',
            'branch'  => $appointment->branch?->localizedName() ?? '',
            'date'    => $appointment->start_time->translatedFormat('l d M Y'),
            'time'    => $appointment->start_time->format('h:i A'),
            'link'    => $link,
        ];
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
        $first      = $visit->first();
        $branch     = $first->branch?->localizedName() ?? '';
        $date       = $first->start_time->translatedFormat('l d M Y');
        $customerId = $first->customer_id;

        $lines = [];
        foreach ($visit as $a) {
            $svc  = $a->service?->localizedName() ?? $a->service?->name ?? '';
            $time = $a->start_time->format('h:i A');
            // Name only the guests who differ from the account holder.
            $guest = ($a->customer_name && $a->customer_id !== $customerId) ? " ({$a->customer_name})" : '';
            $lines[] = "🕐 {$time} — 💇 {$svc}{$guest}";
        }

        $msg = "✅ *تم حجز موعدك بنجاح*\n\n"
            . "📍 *{$branch}*\n"
            . "📅 {$date}\n\n"
            . implode("\n", $lines) . "\n\n";

        if ($visit->count() > 1) {
            $msg .= "💰 الإجمالي: " . (int) $visit->sum('total_price') . "\n\n";
        }

        if ($policy->require_confirmation) {
            $msg .= "✔ لتأكيد الموعد:\n{$confirmUrl}\n\n"
                . "❌ لإلغاء الموعد:\n{$cancelUrl}\n\n";
        }

        return $msg . "نتطلع لرؤيتك! 💛";
    }

    /**
     * SMS channel. Posts to a generic HTTP gateway (works with most local
     * Syrian aggregators and Twilio-style JSON endpoints). Inert until
     * config('booksy.sms') is filled in — returns a clear, logged reason so
     * nothing silently disappears.
     *
     * @return array{0: bool, 1: ?string} [ok, error]
     */
    private function dispatchViaSms(string $phone, string $message): array
    {
        $driver = config('booksy.sms.driver', 'rasel');
        $url    = config('booksy.sms.url');
        $apiKey = config('booksy.sms.api_key');

        if (! $url) {
            return [false, 'SMS channel selected but no SMS provider is configured (set BOOKSY_SMS_URL / BOOKSY_SMS_KEY).'];
        }
        if (! $apiKey) {
            return [false, 'SMS channel selected but no API key is configured (set BOOKSY_SMS_KEY).'];
        }

        // Rasel SMS (Syria): X-API-Key header + { to, channel, messageType, content }.
        // Rasel expects digits only, no leading "+" (e.g. 963949863373).
        if ($driver === 'rasel') {
            $response = Http::withHeaders([
                    'X-API-Key'    => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post($url, [
                    'to'          => preg_replace('/\D+/', '', $phone),
                    'channel'     => config('booksy.sms.channel', 'local_sms'),
                    'messageType' => 'free_text',
                    'content'     => ['text' => $message],
                ]);

            return [$response->successful(), $response->successful() ? null : $response->body()];
        }

        // Generic Twilio-style gateway: Bearer key + { to, from, message }.
        $sender = config('booksy.sms.sender', 'GlowRez');
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post($url, [
                'to'      => preg_replace('/\D+/', '', $phone),
                'from'    => $sender,
                'message' => $message,
            ]);

        return [$response->successful(), $response->successful() ? null : $response->body()];
    }

    public function sendAppointmentConfirmed(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $branch = $appointment->branch?->localizedName() ?? '';
        $date   = $appointment->start_time->translatedFormat('l d M Y');
        $time   = $appointment->start_time->format('h:i A');

        $message = "🎉 *تم تأكيد موعدك*\n\n"
            . "📍 {$branch}\n"
            . "📅 {$date} — ⏰ {$time}\n\n"
            . "نراك قريباً! 💛";

        return $this->send($phone, $message, $appointment->company_id, $appointment->id, 'appointment_confirmed', $this->channelFor($phone));
    }

    public function sendAppointmentCancelled(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $branch = $appointment->branch?->localizedName() ?? '';

        $message = "⚠️ *تم إلغاء موعدك*\n\n"
            . "📍 {$branch}\n"
            . "📅 {$appointment->start_time->translatedFormat('l d M Y')}\n\n"
            . "يمكنك حجز موعد جديد في أي وقت 🙏";

        return $this->send($phone, $message, $appointment->company_id, $appointment->id, 'appointment_cancelled', $this->channelFor($phone));
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
        $time   = $first->start_time->format('h:i A');

        $services = [];
        foreach ($visit as $a) {
            $services[] = "💇 " . ($a->service?->localizedName() ?? $a->service?->name ?? '');
        }

        return "⏰ *تذكير: موعدك بعد ساعة*\n\n"
            . "📍 {$branch}\n"
            . "🕐 اليوم الساعة {$time}\n"
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
