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

        // WhatsApp — companyId=null bypasses the plan gate (account security).
        $message = $isAr
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

        $this->whatsapp->send($company->phone, $message, null, null, 'account_verification');

        // Email (best-effort — requires SMTP configured).
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
    }
}
