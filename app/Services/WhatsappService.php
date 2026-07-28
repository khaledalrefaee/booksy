<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentConfirmation;
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

    public function send(string $phone, string $message, ?int $companyId = null, ?int $appointmentId = null, string $type = 'general'): bool
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
            [$ok, $error] = $this->driver === 'meta'
                ? $this->dispatchViaMeta($phone, $message)
                : $this->dispatchViaLocal($phone, $message);

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

    public function sendAppointmentBooked(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $confirmation = AppointmentConfirmation::generateFor($appointment);
        $confirmUrl   = route('appointment.confirm', ['token' => $confirmation->token, 'action' => 'confirm']);
        $cancelUrl    = route('appointment.confirm', ['token' => $confirmation->token, 'action' => 'cancel']);

        $branch  = $appointment->branch?->localizedName() ?? '';
        $service = $appointment->service?->name ?? '';
        $date    = $appointment->start_time->translatedFormat('l d M Y');
        $time    = $appointment->start_time->format('h:i A');

        $message = "✅ *تم حجز موعدك بنجاح*\n\n"
            . "📍 *{$branch}*\n"
            . "💇 {$service}\n"
            . "📅 {$date}\n"
            . "⏰ {$time}\n\n"
            . "✔ لتأكيد الموعد:\n{$confirmUrl}\n\n"
            . "❌ لإلغاء الموعد:\n{$cancelUrl}\n\n"
            . "نتطلع لرؤيتك! 💛";

        return $this->send(
            $phone, $message,
            $appointment->company_id,
            $appointment->id,
            'appointment_booked'
        );
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

        return $this->send($phone, $message, $appointment->company_id, $appointment->id, 'appointment_confirmed');
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

        return $this->send($phone, $message, $appointment->company_id, $appointment->id, 'appointment_cancelled');
    }

    public function sendReminder(Appointment $appointment): bool
    {
        $phone = $appointment->customer_phone ?? $appointment->customer?->phone;
        if (!$phone) return false;

        $alreadySent = WhatsappLog::where('appointment_id', $appointment->id)
            ->where('type', 'reminder')
            ->where('status', 'sent')
            ->exists();
        if ($alreadySent) return false;

        $branch  = $appointment->branch?->localizedName() ?? '';
        $service = $appointment->service?->name ?? '';
        $time    = $appointment->start_time->format('h:i A');

        $message = "⏰ *تذكير بموعدك*\n\n"
            . "📍 {$branch}\n"
            . "💇 {$service}\n"
            . "🕐 اليوم الساعة {$time}\n\n"
            . "نتطلع لرؤيتك! 💛";

        return $this->send($phone, $message, $appointment->company_id, $appointment->id, 'reminder');
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
