<?php

namespace App\Services;

use App\Models\Company;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CompanyVerificationService
{
    /** How long a verification code stays valid, in minutes. */
    private const CODE_TTL_MINUTES = 4;

    public function __construct(private WhatsappService $whatsapp)
    {
    }

    /** Generate a fresh 4-digit code and deliver it over WhatsApp + email. */
    public function send(Company $company): void
    {
        if (! $company->phone) {
            return;
        }

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        OtpCode::query()->create([
            'phone'      => $company->phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->deliver($company, $code);
    }

    private function deliver(Company $company, string $code): void
    {
        $isAr  = app()->getLocale() === 'ar';
        $brand = 'GlowRez';

        // Email is the PRIMARY, most reliable channel — always attempted first.
        // Branded HTML template; best-effort, logs and moves on if SMTP fails.
        if ($company->email) {
            try {
                Mail::to($company->email)->send(new \App\Mail\VerificationCodeMail(
                    $company->owner_name,
                    $code,
                    self::CODE_TTL_MINUTES,
                    $isAr,
                ));
            } catch (\Throwable $e) {
                Log::warning("Account verification email failed: {$e->getMessage()}");
            }
        }

        if (! $company->phone) {
            return;
        }

        // Phone channel is country-routed: Syrian numbers (dial code in
        // booksy.sms.countries) → local SMS (paid, Rasel), everyone else →
        // WhatsApp. companyId=null bypasses the plan gate (account security).
        $channel = $this->whatsapp->channelFor($company->phone);

        $message = $channel === 'sms'
            ? $this->smsMessage($code, $isAr, $brand)
            : $this->whatsappMessage($code, $isAr, $brand);

        $this->whatsapp->send($company->phone, $message, null, null, 'account_verification', $channel);
    }

    /** Rich, formatted WhatsApp body (markdown + emoji are fine here). */
    private function whatsappMessage(string $code, bool $isAr, string $brand): string
    {
        $mins = self::CODE_TTL_MINUTES;

        return $isAr
            ? "🔐 *{$brand}*\n\n"
                . "رمز تأكيد حسابك:\n\n"
                . "*{$code}*\n\n"
                . "⏱️ صالح لمدة {$mins} دقائق\n"
                . "🔒 لا تُشارك هذا الرمز مع أي أحد"
            : "🔐 *{$brand}*\n\n"
                . "Your account verification code:\n\n"
                . "*{$code}*\n\n"
                . "⏱️ Valid for {$mins} minutes\n"
                . "🔒 Never share this code with anyone";
    }

    /**
     * Short, single-line SMS body — no markdown/emoji, to keep it to one paid
     * segment (Arabic is UCS-2: ~70 chars/segment).
     */
    private function smsMessage(string $code, bool $isAr, string $brand): string
    {
        $mins = self::CODE_TTL_MINUTES;

        return $isAr
            ? "{$brand}: رمز تأكيد حسابك هو {$code} (صالح {$mins} دقائق). لا تشاركه مع أحد."
            : "{$brand}: Your verification code is {$code} (valid {$mins} min). Do not share it.";
    }
}
