<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('company.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('company')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            LoginActivityService::record(
                $request, true, Auth::guard('company')->id(), $credentials['email']
            );

            return redirect()->intended(route('company.dashboard'));
        }

        // Failed attempt — log for the owner activity feed (company may be unknown).
        $companyId = \App\Models\Company::where('email', $credentials['email'])->value('id');
        LoginActivityService::record($request, false, $companyId, $credentials['email']);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('auth.failed')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('company')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }
}
