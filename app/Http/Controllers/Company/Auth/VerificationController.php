<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Services\CompanyVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    /** Show the "enter the code we sent you" screen after registration. */
    public function showNotice(): View|RedirectResponse
    {
        $company = Auth::guard('company')->user();

        if ($company->phone_verified_at) {
            return redirect()->route('company.dashboard');
        }

        return view('company.auth.verify', [
            'phone' => $company->phone,
            'email' => $company->email,
        ]);
    }

    /** Verify the submitted code and mark the account confirmed. */
    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:4'],
        ]);

        $company = Auth::guard('company')->user();

        $otp = OtpCode::query()
            ->where('phone', $company->phone)
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        $otp->update(['used_at' => now()]);
        $company->update([
            'phone_verified_at' => now(),
            'email_verified_at' => $company->email_verified_at ?? now(),
        ]);

        return redirect()->route('company.dashboard')
            ->with('status', __('Your account has been verified. Welcome aboard!'));
    }

    /** Re-send a fresh code (rate-limited). */
    public function resend(CompanyVerificationService $verification): RedirectResponse
    {
        $company = Auth::guard('company')->user();

        if ($company->phone_verified_at) {
            return redirect()->route('company.dashboard');
        }

        $recent = OtpCode::query()
            ->where('phone', $company->phone)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recent >= 4) {
            return back()->withErrors(['code' => __('Too many attempts. Please try again in a few minutes.')]);
        }

        $verification->send($company);

        return back()->with('status', __('A new code has been sent.'));
    }
}
