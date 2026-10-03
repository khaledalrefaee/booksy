<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyPasswordResetService;
use App\Services\CompanyVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    /** Step 1: choose a channel and enter the account email/phone. */
    public function showForgot(): View
    {
        return view('company.auth.forgot');
    }

    /** Step 2: generate + deliver a reset code (WhatsApp or email). */
    public function sendCode(Request $request, CompanyPasswordResetService $resets): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:whatsapp,email'],
            'phone'   => ['nullable', 'required_if:channel,whatsapp', 'string', 'max:20'],
            'email'   => ['nullable', 'required_if:channel,email', 'email'],
        ]);

        $channel = $data['channel'];

        if ($channel === 'email') {
            $identifier = $data['email'];
            $company    = $resets->findCompany(CompanyVerificationService::METHOD_EMAIL, $identifier);
            $delivery   = 'email';
        } else {
            $identifier = preg_replace('/\s+/', '', $data['phone']);
            $company    = $resets->findCompany(CompanyVerificationService::METHOD_PHONE, $identifier);
            // Actual transport: Syrian numbers → local SMS, everyone else → WhatsApp.
            $delivery   = $resets->phoneChannel($company->phone ?? $identifier);
        }

        // Always advance to the reset step — never reveal whether the account
        // exists (prevents account enumeration). We only actually send a code
        // when a matching company is found (and it isn't rate-limited).
        if ($company) {
            $resets->sendCode(
                $company,
                $channel === 'email' ? CompanyVerificationService::METHOD_EMAIL : CompanyVerificationService::METHOD_PHONE,
            );

            session(['pw_reset_phone' => $company->phone]);
        } else {
            session(['pw_reset_phone' => null]);
        }

        session([
            'pw_reset_channel'    => $channel,
            'pw_reset_identifier' => $identifier,
            'pw_reset_delivery'   => $delivery,
        ]);

        return redirect()->route('company.password.reset');
    }

    /** Step 3: show the code + new-password form. */
    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('pw_reset_channel')) {
            return redirect()->route('company.password.forgot');
        }

        return view('company.auth.reset', [
            'identifier' => session('pw_reset_identifier'),
            'channel'    => session('pw_reset_channel'),
            'delivery'   => session('pw_reset_delivery', session('pw_reset_channel')),
        ]);
    }

    /** Step 4: verify the code and set the new password. */
    public function reset(Request $request, CompanyVerificationService $codes): RedirectResponse
    {
        $data = $request->validate([
            'code'     => ['required', 'string', 'size:4'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => __('The passwords do not match.'),
        ]);

        $phone   = session('pw_reset_phone');
        $company = $phone ? Company::query()->where('phone', $phone)->first() : null;
        $result  = $company ? $codes->consumeCode($company, $data['code']) : CompanyVerificationService::CODE_INVALID;

        if ($result === CompanyVerificationService::CODE_LOCKED) {
            return back()->withErrors(['code' => __('Too many incorrect attempts. Please request a new code.')]);
        }

        if ($result !== CompanyVerificationService::CODE_OK) {
            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        // The Company model casts `password` as `hashed`, so it is hashed on save.
        // Clearing api_token signs out any mobile session opened with the old one.
        $company->forceFill(['password' => $data['password'], 'api_token' => null])->save();

        $request->session()->forget(['pw_reset_phone', 'pw_reset_channel', 'pw_reset_identifier', 'pw_reset_delivery']);

        return redirect()->route('company.login')
            ->with('status', __('Your password has been reset. You can now sign in.'));
    }
}
