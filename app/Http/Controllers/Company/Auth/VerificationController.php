<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
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
    public function verify(Request $request, CompanyVerificationService $verification): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:4'],
        ]);

        $company = Auth::guard('company')->user();
        $result  = $verification->consumeCode($company, $data['code']);

        if ($result === CompanyVerificationService::CODE_LOCKED) {
            return back()->withErrors(['code' => __('Too many incorrect attempts. Please request a new code.')]);
        }

        if ($result !== CompanyVerificationService::CODE_OK) {
            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        $verification->markVerified($company);

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

        if ($verification->retryAfter($company) !== null) {
            return back()->withErrors(['code' => __('Too many attempts. Please try again in a few minutes.')]);
        }

        $verification->send($company);

        return back()->with('status', __('A new code has been sent.'));
    }
}
