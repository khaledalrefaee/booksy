<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentConfirmation;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
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
        $log = WhatsappLog::create([
            'company_id'     => $companyId,
            'appointment_id' => $appointmentId,
            'phone'          => $phone,
            'type'           => $type,
            'message'        => $message,
            'status'         => 'queued',
        ]);

        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout(30)
                ->post("{$this->baseUrl}/send", [
                    'phone'   => $phone,
                    'message' => $message,
                ]);

            if ($response->ok()) {
                $log->update(['status' => 'sent', 'sent_at' => now()]);
                return true;
            }

            $log->update(['status' => 'failed', 'error' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::warning("WhatsApp send failed: {$e->getMessage()}");
            return false;
        }
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
}
