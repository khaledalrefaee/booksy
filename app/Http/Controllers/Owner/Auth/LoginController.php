<?php

namespace App\Http\Controllers\Owner\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('owner.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('owner')->attempt($credentials, $remember)) {
            $owner = Auth::guard('owner')->user();

            // Disabled staff never get a session.
            if ($owner->is_active === false) {
                Auth::guard('owner')->logout();

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('Your account has been disabled.')]);
            }

            $request->session()->regenerate();
            $owner->forceFill(['last_login_at' => now()])->saveQuietly();

            // First-login accounts must set their own password before anything.
            if ($owner->must_change_password) {
                return redirect()->route('owner.password.change');
            }

            // Route by capability: admins to the panel, field reps to /employee.
            return redirect()->intended(route($owner->homeRoute()));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('auth.failed')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('owner')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}
