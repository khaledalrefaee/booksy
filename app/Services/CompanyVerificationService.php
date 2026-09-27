<?php

namespace App\Services;

use App\Models\Company;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Company one-time codes: account verification after sign-up, plus the shared
 * code checker the password-reset flow reuses. Used by BOTH the web panel
 * (Company\Auth\*) and the mobile API (Api\Companies\AuthController), so the
 * rules — TTL, resend cooldown, attempt limit — live in one place.
 *
 * Codes are stored in `otp_codes`, keyed by the company's phone.
 */
class CompanyVerificationService
{
    /** How long a verification code stays valid, in minutes. */
    public const CODE_TTL_MINUTES = 4;

    /** Minimum gap between two codes for the same account, in seconds. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** At most this many codes per account inside the window below. */
    public const MAX_CODES_PER_WINDOW = 4;
    public const WINDOW_MINUTES = 10;

    /** Wrong guesses allowed before every outstanding code is burned. */
    public const MAX_ATTEMPTS = 5;

    /** consumeCode() outcomes. */
    public const CODE_OK      = 'ok';
    public const CODE_INVALID = 'invalid';
    public const CODE_LOCKED  = 'locked';

    /** Identifier kinds for password recovery (API "sms" maps to phone). */
    public const METHOD_EMAIL = 'email';
    public const METHOD_PHONE = 'phone';

    public function __construct(private WhatsappService $whatsapp)
    {
    }

    /**
     * Generate a fresh 4-digit code and deliver the SAME code over email AND
     * phone (one code, one input — used by both the web panel and the API).
     * Returns the plain code (callers must never expose it outside local dev).
     */
    public function send(Company $company): ?string
    {
        if (! $company->phone) {
            return null;
        }

        $code = self::newCode();

        OtpCode::query()->create([
            'phone'      => $company->phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->deliver($company, $code);

        return $code;
    }

    /**
     * Seconds the account must wait before another code may be sent, or null
     * when sending is allowed now. Enforces the cooldown and the window cap.
     */
    public function retryAfter(Company $company, int $maxPerWindow = self::MAX_CODES_PER_WINDOW): ?int
    {
        return self::retryAfterFor((string) $company->phone, $maxPerWindow);
    }

    /** Shared cooldown/window check for any code keyed by this phone. */
    public static function retryAfterFor(string $phone, int $maxPerWindow): ?int
    {
        $recent = OtpCode::query()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->orderByDesc('created_at')
            ->get(['created_at']);

        if ($recent->count() >= $maxPerWindow) {
            // Allowed again once the code that keeps us at the cap ages out.
            $blocking = $recent[$maxPerWindow - 1]->created_at;

            return max(1, (int) now()->diffInSeconds($blocking->copy()->addMinutes(self::WINDOW_MINUTES), true));
        }

        $latest = $recent->first()?->created_at;
        if ($latest && $latest->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->isFuture()) {
            return max(1, (int) now()->diffInSeconds($latest->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS), true));
        }

        return null;
    }

    /**
     * Check a submitted code against the account's live codes and consume it.
     * After MAX_ATTEMPTS wrong guesses every outstanding code is burned, so a
     * 4-digit code can't be brute-forced — the user must request a new one.
     */
    public function consumeCode(Company $company, string $code): string
    {
        $key = 'company-otp:'.sha1((string) $company->phone);

        $otp = OtpCode::query()
            ->where('phone', $company->phone)
            ->where('code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($otp) {
            $otp->update(['used_at' => now()]);
            RateLimiter::clear($key);

            return self::CODE_OK;
        }

        RateLimiter::hit($key, self::WINDOW_MINUTES * 60);

        if (RateLimiter::attempts($key) >= self::MAX_ATTEMPTS) {
            OtpCode::query()
                ->where('phone', $company->phone)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);
            RateLimiter::clear($key);

            return self::CODE_LOCKED;
        }

        return self::CODE_INVALID;
    }

    /** Mark the account confirmed and seed its head-office branch. */
    public function markVerified(Company $company): void
    {
        $company->update([
            'phone_verified_at' => now(),
            'email_verified_at' => $company->email_verified_at ?? now(),
        ]);

        // Seed the head-office branch so the setup checklist can begin.
        CompanySetupService::ensureHeadOffice($company);
    }

    /** A random zero-padded 4-digit code. */
    public static function newCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
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
