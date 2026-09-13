<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\OtpCode;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mobile customer authentication — phone + one-time code.
 *
 * Flow (all responses share the {status, message, data} envelope):
 *   1. POST send-code   { phone }          → sends a 4-digit code. Syrian numbers
 *                                            (dial code in booksy.sms.countries) go
 *                                            over local SMS (Rasel); every other
 *                                            country goes over WhatsApp.
 *   2. POST verify-code { phone, code }    → creates/loads the customer, marks the
 *                                            phone verified, returns a bearer token.
 *   3. POST profile     { name, date_of_birth } → completes the profile. Age is
 *                                            derived from date_of_birth, never asked.
 *   4. POST avatar      { avatar (file) }  → uploads the profile photo. Optional at
 *                                            sign-up; can be done later from profile.
 *
 * Language follows the request locale (SetApiLocale: ?lang / Accept-Language).
 */
class AuthController extends ApiController
{
    /** How long a code stays valid, in minutes. */
    private const CODE_TTL_MINUTES = 4;

    /** Step 1 — deliver a verification code to the phone. */
    public function sendCode(Request $request, WhatsappService $whatsapp): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $phone = $this->normalizePhone($request->input('phone'));

        // Rate limit: at most 3 codes per 10 minutes per number.
        $recent = OtpCode::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recent >= 3) {
            return $this->error(__('Too many attempts. Please try again in a few minutes.'), 429);
        }

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        // Route by country and deliver. companyId=null bypasses the plan gate —
        // this is account security, not a marketing message.
        $channel = $whatsapp->channelFor($phone);
        $message = $this->codeMessage($code, $channel);
        $whatsapp->send($phone, $message, null, null, 'otp', $channel);

        $isDev = app()->environment('local');

        return $this->success([
            'phone'      => $phone,
            'channel'    => $channel,                 // "sms" (Syria) or "whatsapp"
            'expires_in' => self::CODE_TTL_MINUTES * 60,
            // dev only — surfaced so the flow is testable without a live gateway.
            'dev_code'   => $isDev ? $code : null,
        ], __('A verification code has been sent.'));
    }

    /** Step 2 — verify the code, upsert the customer, return a bearer token. */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'code'  => ['required', 'string', 'size:4'],
        ]);

        $phone = $this->normalizePhone($request->input('phone'));

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $request->input('code'))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return $this->error(__('The code is invalid or has expired.'), 422);
        }

        $otp->update(['used_at' => now()]);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name'  => '', 'source' => 'website'],
        );

        if ($customer->is_banned) {
            return $this->error(__('This account has been suspended.'), 403);
        }

        if (! $customer->phone_verified_at) {
            $customer->phone_verified_at = now();
        }

        // Issue a fresh bearer token (invalidates any previous device session).
        $plain = Str::random(64);
        $customer->api_token = hash('sha256', $plain);
        $customer->save();

        return $this->success([
            'token'         => $plain,                 // send as: Authorization: Bearer <token>
            'token_type'    => 'Bearer',
            'needs_profile' => $this->needsProfile($customer),
            'customer'      => $this->present($customer),
        ], __('Your phone number has been verified.'));
    }

    /** GET the signed-in customer. */
    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'customer' => $this->present($this->current($request)),
        ]);
    }

    /**
     * Complete or update the profile: name + date of birth (age is derived), and
     * OPTIONALLY the avatar in the same call — so a customer can set everything at
     * sign-up. The photo can still be added/replaced later via POST avatar.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'avatar'        => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $customer = $this->current($request);
        $customer->name = $data['name'];

        if (array_key_exists('date_of_birth', $data)) {
            $customer->date_of_birth = $data['date_of_birth'];
            // Keep the age column in sync so lists that read it stay correct.
            $customer->age = $customer->date_of_birth
                ? \Illuminate\Support\Carbon::parse($customer->date_of_birth)->age
                : null;
        }

        if ($request->hasFile('avatar')) {
            $this->storeAvatar($customer, $request->file('avatar'));
        }

        $customer->save();

        return $this->success([
            'customer' => $this->present($customer),
        ], __('Your profile has been saved.'));
    }

    /** Step 4 — upload (or replace) the profile photo. */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5 MB
        ]);

        $customer = $this->current($request);
        $this->storeAvatar($customer, $request->file('avatar'));
        $customer->save();

        return $this->success([
            'customer' => $this->present($customer),
        ], __('Your photo has been updated.'));
    }

    /** Store a new avatar file on the customer, removing the previous one. */
    private function storeAvatar(Customer $customer, \Illuminate\Http\UploadedFile $file): void
    {
        if ($customer->avatar) {
            Storage::disk('public')->delete($customer->avatar);
        }

        $customer->avatar = $file->store('customers/avatars', 'public');
    }

    /** Sign out this device by clearing the stored token. */
    public function logout(Request $request): JsonResponse
    {
        $customer = $this->current($request);
        $customer->api_token = null;
        $customer->save();

        return $this->success(null, __('You have been signed out.'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** The customer resolved by AuthenticateCustomerApi for this request. */
    private function current(Request $request): Customer
    {
        return $request->attributes->get('customer');
    }

    /** A profile is incomplete until it has a name. */
    private function needsProfile(Customer $customer): bool
    {
        return $customer->name === '' || $customer->name === null;
    }

    /** The public shape of a customer returned to the app. */
    private function present(Customer $customer): array
    {
        $dob = $customer->date_of_birth
            ? \Illuminate\Support\Carbon::parse($customer->date_of_birth)
            : null;

        return [
            'id'             => $customer->id,
            'name'           => $customer->name,
            'phone'          => $customer->phone,
            'date_of_birth'  => $dob?->toDateString(),
            'age'            => $dob ? $dob->age : $customer->age,   // derived from DOB
            'avatar'         => $customer->avatar ? asset('storage/' . $customer->avatar) : null,
            'has_avatar'     => (bool) $customer->avatar,
            'loyalty_points' => $customer->loyalty_points,
            'verified'       => (bool) $customer->phone_verified_at,
            'needs_profile'  => $this->needsProfile($customer),
        ];
    }

    /** Strip spaces and any non-digit noise from a submitted phone number. */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone));
    }

    /**
     * The code message body, localised and channel-appropriate. WhatsApp allows
     * rich formatting; SMS stays a single plain line to keep it to one paid
     * segment (Arabic is UCS-2, ~70 chars/segment).
     */
    private function codeMessage(string $code, string $channel): string
    {
        $isAr  = app()->getLocale() === 'ar';
        $mins  = self::CODE_TTL_MINUTES;
        $brand = 'GlowRez';

        if ($channel === 'sms') {
            return $isAr
                ? "{$brand}: رمز التحقق الخاص بك هو {$code} (صالح {$mins} دقائق). لا تشاركه مع أحد."
                : "{$brand}: Your verification code is {$code} (valid {$mins} min). Do not share it.";
        }

        return $isAr
            ? "🔐 *{$brand}*\n\n"
                . "رمز التحقق الخاص بك:\n\n"
                . "*{$code}*\n\n"
                . "⏱️ صالح لمدة {$mins} دقائق\n"
                . "🔒 لا تُشارك هذا الرمز مع أي أحد"
            : "🔐 *{$brand}*\n\n"
                . "Your verification code:\n\n"
                . "*{$code}*\n\n"
                . "⏱️ Valid for {$mins} minutes\n"
                . "🔒 Never share this code with anyone";
    }
}
