<?php

namespace App\Services;

use App\Models\Company;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Company "forgot password" — shared by the web panel (Company\Auth\
 * PasswordResetController) and the mobile API (Api\Companies\AuthController).
 *
 *   1. findCompany() + sendCode()   → a 4-digit code over email or phone.
 *   2. CompanyVerificationService::consumeCode() checks it (attempt-limited).
 *   3. API only: issueResetToken() hands out a short-lived one-time token that
 *      consumeResetToken() redeems when the new password is set.
 */
class CompanyPasswordResetService
{
    /** How long a reset code stays valid, in minutes. */
    public const CODE_TTL_MINUTES = 10;

    /** Reset codes allowed per account inside the verification window. */
    public const MAX_CODES_PER_WINDOW = 3;

    /** How long the post-verification reset token lives, in minutes. */
    public const TOKEN_TTL_MINUTES = 15;

    public function __construct(private WhatsappService $whatsapp)
    {
    }

    /**
     * Resolve the account from an email or phone. Phones are matched with and
     * without the leading "+" so "9639…" and "+9639…" both work.
     */
    public function findCompany(string $method, string $value): ?Company
    {
        if ($method === CompanyVerificationService::METHOD_EMAIL) {
            return Company::query()->where('email', trim($value))->first();
        }

        $phone  = preg_replace('/[\s\-()]+/', '', trim($value));
        $digits = ltrim($phone, '+');

        return Company::query()->whereIn('phone', array_unique([$phone, '+'.$digits, $digits]))->first();
    }

    /**
     * Send a reset code over the chosen method ('email' | 'phone'). Silently
     * does nothing while the account is in cooldown / over its window cap, so
     * the caller's response never reveals anything. Returns the code when one
     * was sent (callers must never expose it outside local dev).
     */
    public function sendCode(Company $company, string $method): ?string
    {
        if (! $company->phone
            || CompanyVerificationService::retryAfterFor($company->phone, self::MAX_CODES_PER_WINDOW) !== null) {
            return null;
        }

        $code = CompanyVerificationService::newCode();

        OtpCode::query()->create([
            'phone'      => $company->phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->deliver($company, $code, $method);

        return $code;
    }

    /** The transport a phone reset code actually uses: "sms" or "whatsapp". */
    public function phoneChannel(string $phone): string
    {
        return $this->whatsapp->channelFor($phone);
    }

    /** Issue a one-time token that authorises setting a new password. */
    public function issueResetToken(Company $company): string
    {
        $plain = Str::random(64);

        Cache::put(self::tokenKey($plain), $company->id, now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return $plain;
    }

    /** Redeem a reset token — it is deleted on read, so it works exactly once. */
    public function consumeResetToken(string $plain): ?Company
    {
        $companyId = Cache::pull(self::tokenKey($plain));

        return $companyId ? Company::query()->find($companyId) : null;
    }

    /** Only the token's hash is used as the cache key — never the plaintext. */
    private static function tokenKey(string $plain): string
    {
        return 'company-pw-reset:'.hash('sha256', $plain);
    }

    /** Send the reset code over the chosen channel. Failures are non-fatal. */
    private function deliver(Company $company, string $code, string $method): void
    {
        $isAr  = app()->getLocale() === 'ar';
        $brand = 'GlowRez';
        $mins  = self::CODE_TTL_MINUTES;

        if ($method === CompanyVerificationService::METHOD_EMAIL) {
            $subject = $isAr ? "رمز استعادة كلمة المرور — {$brand}" : "Password reset code — {$brand}";
            $body = $isAr
                ? "رمز استعادة كلمة المرور الخاص بك هو: {$code}\n\nالرمز صالح لمدة {$mins} دقائق. إن لم تطلب ذلك، تجاهل هذه الرسالة."
                : "Your password reset code is: {$code}\n\nThe code is valid for {$mins} minutes. If you didn't request this, please ignore this email.";

            try {
                Mail::raw($body, function ($m) use ($company, $subject) {
                    $m->to($company->email)->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::warning("Password reset email failed: {$e->getMessage()}");
            }

            return;
        }

        // Deliver to the phone, routed by country: Syrian numbers → local SMS
        // (paid, Rasel), everyone else → WhatsApp. companyId=null so the plan
        // gate never blocks an account-security message.
        $phoneChannel = $this->phoneChannel($company->phone);

        // Short, single-line body for the paid SMS segment; rich body for WhatsApp.
        $message = $phoneChannel === 'sms'
            ? ($isAr
                ? "{$brand}: رمز استعادة كلمة المرور هو {$code} (صالح {$mins} دقائق). لا تشاركه مع أحد."
                : "{$brand}: Your password reset code is {$code} (valid {$mins} min). Do not share it.")
            : ($isAr
                ? "🔐 *{$brand}*\n\n"
                    . "رمز استعادة كلمة المرور:\n\n"
                    . "*{$code}*\n\n"
                    . "⏱️ صالح لمدة {$mins} دقائق\n"
                    . "🔒 لا تُشارك هذا الرمز مع أي أحد"
                : "🔐 *{$brand}*\n\n"
                    . "Your password reset code:\n\n"
                    . "*{$code}*\n\n"
                    . "⏱️ Valid for {$mins} minutes\n"
                    . "🔒 Never share this code with anyone");

        $this->whatsapp->send($company->phone, $message, null, null, 'password_reset', $phoneChannel);
    }
}
