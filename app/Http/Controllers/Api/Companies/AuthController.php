<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company;
use App\Services\CompanyPasswordResetService;
use App\Services\CompanyVerificationService;
use App\Services\LoginActivityService;
use App\Services\OwnerNotificationService;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mobile company (business owner) authentication — the company twin of
 * Api\Customers\AuthController. Same {status, message, data} envelope, same
 * bearer-token scheme (sha256 in companies.api_token), same otp_codes table.
 * OTP rules (TTL, cooldown, attempt limit) are shared with the web panel via
 * CompanyVerificationService / CompanyPasswordResetService.
 *
 * Flow:
 *   1. POST register              → creates the company (unverified; status
 *                                   "pending" = awaiting owner approval) and
 *                                   sends ONE code to both email and phone.
 *   2. POST verify                { company_id, code } → email + phone verified, token.
 *      POST resend                { company_id } → a fresh code to both.
 *   3. POST login                 { email, password } → token (verified only).
 *   4. POST password/forgot       { method, value } → reset code.
 *      POST password/verify       { method, value, code } → one-time reset_token.
 *      POST password/reset        { reset_token, password, password_confirmation }.
 *
 * "method" is "email" or "sms". "sms" means the phone: Syrian numbers get a
 * local SMS, other countries get WhatsApp — `channel` in the response says which.
 */
class AuthController extends ApiController
{
    public function __construct(
        private CompanyVerificationService $verification,
        private CompanyPasswordResetService $resets,
        private WhatsappService $whatsapp,
    ) {
    }

    /** Step 1 — create the company account and send the verification code. */
    public function register(Request $request): JsonResponse
    {
        // Same fields and rules as the web sign-up (Company\Auth\RegisterController).
        $data = $request->validate([
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['nullable', 'string', 'max:255'],
            'owner_name'          => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'unique:companies,email'],
            'phone'               => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/', 'unique:companies,phone'],
            'category_id'         => ['required', 'exists:categories,id'],
            'password'            => ['required', 'string', 'min:8','confirmed'],
            'terms'               => ['accepted'],
        ], [
            'phone.regex'    => __('Please enter a valid phone number.'),
            'phone.unique'   => __('This phone number is already registered.'),
            'terms.accepted' => __('You must agree to the Terms of Service and Privacy Policy.'),
        ]);

        $company = Company::query()->create([
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'owner_name'  => $data['owner_name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'category_id' => $data['category_id'],
            'password'    => Hash::make($data['password']),
            'status'      => 'pending',
        ]);

        OwnerNotificationService::businessRegistered($company);

        // ONE code, delivered to both the email inbox and the phone.
        $code = $this->verification->send($company);

        return $this->success(
            $this->codeSentPayload($company, $code),
            __('Verification code sent to your email and phone.'),
            201,
        );
    }

    /** Step 2 — confirm the sign-up code and sign the company in. */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'code'       => ['required', 'string', 'size:4'],
        ]);

        $company = Company::query()->find($data['company_id']);

        if (! $company) {
            return $this->error(__('The code is invalid or has expired.'), 422);
        }

        if ($company->isVerified()) {
            return $this->error(__('This account is already verified. Please sign in.'), 409);
        }

        if ($error = $this->codeError($this->verification->consumeCode($company, $data['code']))) {
            return $error;
        }

        // The code reached both inboxes → email_verified_at + phone_verified_at.
        $this->verification->markVerified($company);

        if ($company->isSuspended()) {
            return $this->error($company->suspendedNotice(), 403);
        }

        return $this->success(
            $this->issueToken($company),
            __('Account verified successfully.'),
        );
    }

    /** Re-send a fresh code to email + phone (cooldown + window cap, unverified only). */
    public function resend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $company = Company::query()->find($data['company_id']);

        if (! $company) {
            return $this->error(__('Not found.'), 404);
        }

        if ($company->isVerified()) {
            return $this->error(__('This account is already verified. Please sign in.'), 409);
        }

        if ($wait = $this->verification->retryAfter($company)) {
            return $this->error(
                __('Please wait :seconds seconds before requesting a new code.', ['seconds' => $wait]),
                429,
                null,
                ['retry_after' => $wait],
            );
        }

        $code = $this->verification->send($company);

        return $this->success(
            $this->codeSentPayload($company, $code),
            __('Verification code sent to your email and phone.'),
        );
    }

    /** Sign in with the same credentials as the web panel: email + password. */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $company = Company::query()->where('email', $data['email'])->first();

        if (! $company || ! Hash::check($data['password'], $company->password)) {
            LoginActivityService::record($request, false, $company?->id, $data['email']);

            return $this->error(__('auth.failed'), 422, ['email' => [__('auth.failed')]]);
        }

        if ($company->isSuspended()) {
            return $this->error($company->suspendedNotice(), 403);
        }

        // Correct password but the sign-up code was never confirmed: tell the
        // app which account to verify (it then calls resend → verify).
        if (! $company->isVerified()) {
            return $this->error(__('Please verify your account to continue.'), 403, null, [
                'verification_required' => true,
                'company_id'            => $company->id,
                'email'                 => $this->maskEmail($company->email),
                'phone'                 => $this->maskPhone($company->phone),
                'phone_channel'         => $this->phoneChannel($company),
            ]);
        }

        LoginActivityService::record($request, true, $company->id, $company->email);

        return $this->success(
            $this->issueToken($company),
            __('Signed in successfully.'),
        );
    }

    /**
     * Forgot password, step 1 — send a reset code by email or SMS. The answer is
     * identical whether or not the account exists (no account enumeration).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $this->validateIdentifier($request);

        $company = $this->resets->findCompany($this->serviceMethod($data['method']), $data['value']);
        $code    = $company ? $this->resets->sendCode($company, $this->serviceMethod($data['method'])) : null;

        return $this->success([
            'method'     => $data['method'],
            'expires_in' => CompanyPasswordResetService::CODE_TTL_MINUTES * 60,
            // dev only — surfaced so the flow is testable without a live gateway.
            'dev_code'   => $this->devCode($code),
        ], __('If the details match an account, a verification code has been sent.'));
    }

    /** Forgot password, step 2 — check the code and hand out a one-time reset token. */
    public function verifyResetCode(Request $request): JsonResponse
    {
        $data = $this->validateIdentifier($request, [
            'code' => ['required', 'string', 'size:4'],
        ]);

        $company = $this->resets->findCompany($this->serviceMethod($data['method']), $data['value']);

        if (! $company) {
            return $this->error(__('The code is invalid or has expired.'), 422);
        }

        if ($error = $this->codeError($this->verification->consumeCode($company, $data['code']))) {
            return $error;
        }

        return $this->success([
            'reset_token' => $this->resets->issueResetToken($company),
            'expires_in'  => CompanyPasswordResetService::TOKEN_TTL_MINUTES * 60,
        ], __('Code verified successfully.'));
    }

    /** Forgot password, step 3 — set the new password (token works once). */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reset_token' => ['required', 'string'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $company = $this->resets->consumeResetToken($data['reset_token']);

        if (! $company) {
            return $this->error(__('The reset token is invalid or has expired.'), 422);
        }

        // `password` is cast as hashed. Clearing api_token signs out every device.
        $company->forceFill(['password' => $data['password'], 'api_token' => null])->save();

        return $this->success(null, __('Your password has been reset. You can now sign in.'));
    }

    /** GET the signed-in company. */
    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'company' => $this->present($this->current($request)),
        ]);
    }

    /**
     * Update the company profile — the same fields as the web profile page
     * (name_en, name_ar, email, phone) plus an OPTIONAL logo in the same
     * multipart call. Every field is optional: send only what changed.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $company = $this->current($request);

        $data = $request->validate([
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'owner_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email'   => ['sometimes', 'required', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($company->id)],
            'phone'   => ['sometimes', 'required', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/', Rule::unique('companies', 'phone')->ignore($company->id)],
            'logo'    => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'], // 2 MB, as on the web
        ], [
            'phone.regex'  => __('Please enter a valid phone number.'),
            'phone.unique' => __('This phone number is already registered.'),
        ]);

        $company->fill(Arr::only($data, ['name_en', 'name_ar', 'email', 'phone','owner_name']));

        if ($request->hasFile('logo')) {
            $this->storeLogo($company, $request->file('logo'));
        }

        $company->save();

        return $this->success([
            'company' => $this->present($company),
        ], __('Profile updated successfully.'));
    }

    /** Upload (or replace) the company logo on its own. */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'], // 2 MB
        ]);

        $company = $this->current($request);
        $this->storeLogo($company, $request->file('logo'));
        $company->save();

        return $this->success([
            'company' => $this->present($company),
        ], __('Your logo has been updated.'));
    }

    /** Remove the company logo. */
    public function deleteLogo(Request $request): JsonResponse
    {
        $company = $this->current($request);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
            $company->logo = null;
            $company->save();
        }

        return $this->success([
            'company' => $this->present($company),
        ], __('Your logo has been removed.'));
    }

    /** Store a new logo on the company (same folder as the web), removing the old file. */
    private function storeLogo(Company $company, UploadedFile $file): void
    {
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->logo = $file->store('companies/logos', 'public');
    }

    /** Sign out this device by clearing the stored token. */
    public function logout(Request $request): JsonResponse
    {
        $company = $this->current($request);
        $company->forceFill(['api_token' => null])->save();

        return $this->success(null, __('You have been signed out.'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** The company resolved by AuthenticateCompanyApi for this request. */
    private function current(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    /** Validate { method, value } (+ extra rules); value must match the method. */
    private function validateIdentifier(Request $request, array $extra = []): array
    {
        $valueRules = $request->input('method') === 'email'
            ? ['required', 'email', 'max:255']
            : ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{8,20}$/'];

        return $request->validate([
            'method' => ['required', 'in:email,sms'],
            'value'  => $valueRules,
        ] + $extra, [
            'value.regex' => __('Please enter a valid phone number.'),
        ]);
    }

    /** API method ("email" | "sms") → service method ("email" | "phone"). */
    private function serviceMethod(string $method): string
    {
        return $method === 'email'
            ? CompanyVerificationService::METHOD_EMAIL
            : CompanyVerificationService::METHOD_PHONE;
    }

    /** Map a consumeCode() outcome to an error response (null when it passed). */
    private function codeError(string $result): ?JsonResponse
    {
        return match ($result) {
            CompanyVerificationService::CODE_OK     => null,
            CompanyVerificationService::CODE_LOCKED => $this->error(__('Too many incorrect attempts. Please request a new code.'), 429),
            default                                 => $this->error(__('The code is invalid or has expired.'), 422),
        };
    }

    /** Issue a fresh bearer token (invalidates any previous device session). */
    private function issueToken(Company $company): array
    {
        $plain = Str::random(64);
        $company->forceFill(['api_token' => hash('sha256', $plain)])->save();

        return [
            'token'      => $plain,              // send as: Authorization: Bearer <token>
            'token_type' => 'Bearer',
            'company'    => $this->present($company),
        ];
    }

    /** What the app needs to open the OTP screen — never the code itself. */
    private function codeSentPayload(Company $company, ?string $code): array
    {
        return [
            'company_id'     => $company->id,
            'email_verified' => false,
            'phone_verified' => false,
            'email'          => $this->maskEmail($company->email),
            'phone'          => $this->maskPhone($company->phone),
            'phone_channel'  => $this->phoneChannel($company),   // "sms" (Syria) | "whatsapp"
            'expires_in'     => CompanyVerificationService::CODE_TTL_MINUTES * 60,
            'resend_after'   => CompanyVerificationService::RESEND_COOLDOWN_SECONDS,
            // dev only — surfaced so the flow is testable without a live gateway.
            'dev_code'       => $this->devCode($code),        // same code went to email + phone
        ];
    }

    /** The transport the phone code actually uses. */
    private function phoneChannel(Company $company): string
    {
        return $this->whatsapp->channelFor((string) $company->phone);
    }

    /** Same rule as the customer API: the code is echoed ONLY in local dev. */
    private function devCode(?string $code): ?string
    {
        return app()->environment('local') ? $code : null;
    }

    /** The public shape of a company returned to the app (web registration order). */
    private function present(Company $company): array
    {
        $company->loadMissing('category');

        return [
            'id'                      => $company->id,
            'name'                    => $company->localizedName(),
            'name_en'                 => $company->name_en,
            'name_ar'                 => $company->name_ar,
            'owner_name'              => $company->owner_name,
            'email'                   => $company->email,
            'phone'                   => $company->phone,
            'category'                => $company->category ? [
                'id'   => $company->category->id,
                'name' => $company->category->localizedName(),
            ] : null,
            'logo'                    => $company->logo ? asset('storage/'.$company->logo) : null,
            'status'                  => $company->status,   // pending | active | suspended
            'verified'                => $company->isVerified(),
            'email_verified'          => $company->email_verified_at !== null,
            'phone_verified'          => $company->phone_verified_at !== null,
            'email_verified_at'       => $company->email_verified_at?->toIso8601String(),
            'phone_verified_at'       => $company->phone_verified_at?->toIso8601String(),
            'submitted_for_review_at' => $company->submitted_for_review_at?->toIso8601String(),
            'created_at'              => $company->created_at?->toIso8601String(),
        ];
    }

    /** "khaled@example.com" → "kh****@example.com". */
    private function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 2).str_repeat('*', max(2, mb_strlen($local) - 2)).'@'.$domain;
    }

    /** "+963991234567" → "********4567". */
    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        return str_repeat('*', max(0, strlen($phone) - 4)).substr($phone, -4);
    }
}
