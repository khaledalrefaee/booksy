<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells a business owner their account access changed. Right now this is the
 * suspension notice (email + phone), sent the moment the platform flips a
 * company to "suspended". Reuses the OTP delivery pattern: email is the primary
 * channel, phone is country-routed (Syrian → SMS, everyone else → WhatsApp),
 * companyId=null so the plan gate never swallows an account-security message.
 */
class CompanyStatusNotifier
{
    public function __construct(private WhatsappService $whatsapp)
    {
    }

    public function sendSuspended(Company $company, ?string $reason = null): void
    {
        $isAr  = app()->getLocale() === 'ar';
        $brand = 'GlowRez';
        $name  = $company->owner_name ?: $company->localizedName();

        $this->emailSuspended($company, $isAr, $brand, $name, $reason);
        $this->phoneSuspended($company, $isAr, $brand, $reason);
    }

    private function emailSuspended(Company $company, bool $isAr, string $brand, ?string $name, ?string $reason): void
    {
        if (! $company->email) {
            return;
        }

        $subject = $isAr ? "تم إيقاف حسابك — {$brand}" : "Your account has been suspended — {$brand}";

        $reasonLine = $reason
            ? ($isAr ? "\n\nالسبب: {$reason}" : "\n\nReason: {$reason}")
            : '';

        $body = $isAr
            ? "مرحباً {$name},\n\nنأسف لإبلاغك بأنه تم إيقاف حساب مركزك على {$brand} مؤقتاً، ولن تتمكن من تسجيل الدخول حالياً.{$reasonLine}\n\nلمراجعة الأمر أو إعادة تفعيل الحساب، يرجى التواصل مع فريق الدعم.\n\nفريق {$brand}"
            : "Hello {$name},\n\nWe're writing to let you know that your {$brand} business account has been suspended. You will not be able to sign in for now.{$reasonLine}\n\nTo review this or reactivate your account, please contact our support team.\n\nThe {$brand} Team";

        try {
            Mail::raw($body, function ($m) use ($company, $subject) {
                $m->to($company->email)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning("Company suspension email failed: {$e->getMessage()}");
        }
    }

    private function phoneSuspended(Company $company, bool $isAr, string $brand, ?string $reason): void
    {
        if (! $company->phone) {
            return;
        }

        $channel = $this->whatsapp->channelFor($company->phone);

        $message = $channel === 'sms'
            ? $this->smsMessage($isAr, $brand, $reason)
            : $this->whatsappMessage($isAr, $brand, $reason);

        // companyId=null → bypass the plan gate (this is an account-security notice).
        $this->whatsapp->send($company->phone, $message, null, null, 'account_suspended', $channel);
    }

    /** Rich WhatsApp body (markdown + emoji are fine here). */
    private function whatsappMessage(bool $isAr, string $brand, ?string $reason): string
    {
        $reasonLine = $reason
            ? ($isAr ? "\n📝 السبب: {$reason}" : "\n📝 Reason: {$reason}")
            : '';

        return $isAr
            ? "⛔ *{$brand}*\n\nتم إيقاف حساب مركزك مؤقتاً ولا يمكنك تسجيل الدخول حالياً.{$reasonLine}\n\nللمراجعة يرجى التواصل مع الدعم."
            : "⛔ *{$brand}*\n\nYour business account has been suspended and you can't sign in right now.{$reasonLine}\n\nPlease contact support to review this.";
    }

    /** Short single-segment SMS body — no markdown/emoji. */
    private function smsMessage(bool $isAr, string $brand, ?string $reason): string
    {
        $reasonLine = $reason
            ? ($isAr ? " السبب: {$reason}." : " Reason: {$reason}.")
            : '';

        return $isAr
            ? "{$brand}: تم إيقاف حساب مركزك مؤقتاً ولا يمكنك تسجيل الدخول.{$reasonLine} للمراجعة تواصل مع الدعم."
            : "{$brand}: Your business account has been suspended; you cannot sign in.{$reasonLine} Contact support to review.";
    }
}
