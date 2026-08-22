<?php

namespace App\Services;

use App\Models\Company;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CompanyVerificationService
{
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
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->deliver($company, $code);
    }

    private function deliver(Company $company, string $code): void
    {
        $isAr  = app()->getLocale() === 'ar';
        $brand = 'GlowRez';

        // Email is the PRIMARY, most reliable channel — always attempted first.
        // (best-effort; silently no-ops until SMTP is configured.)
        if ($company->email) {
            $subject = $isAr ? "رمز تأكيد الحساب — {$brand}" : "Account verification code — {$brand}";
            $body = $isAr
                ? "مرحباً {$company->owner_name},\n\nرمز تأكيد حسابك في {$brand} هو: {$code}\n\nالرمز صالح لمدة 15 دقيقة."
                : "Hello {$company->owner_name},\n\nYour {$brand} account verification code is: {$code}\n\nThe code is valid for 15 minutes.";

            try {
                Mail::raw($body, function ($m) use ($company, $subject) {
                    $m->to($company->email)->subject($subject);
                });
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
        return $isAr
            ? "🔐 *{$brand}*\n\n"
                . "رمز تأكيد حسابك:\n\n"
                . "*{$code}*\n\n"
                . "⏱️ صالح لمدة 15 دقيقة\n"
                . "🔒 لا تُشارك هذا الرمز مع أي أحد"
            : "🔐 *{$brand}*\n\n"
                . "Your account verification code:\n\n"
                . "*{$code}*\n\n"
                . "⏱️ Valid for 15 minutes\n"
                . "🔒 Never share this code with anyone";
    }

    /**
     * Short, single-line SMS body — no markdown/emoji, to keep it to one paid
     * segment (Arabic is UCS-2: ~70 chars/segment).
     */
    private function smsMessage(string $code, bool $isAr, string $brand): string
    {
        return $isAr
            ? "{$brand}: رمز تأكيد حسابك هو {$code} (صالح 15 دقيقة). لا تشاركه مع أحد."
            : "{$brand}: Your verification code is {$code} (valid 15 min). Do not share it.";
    }
}
