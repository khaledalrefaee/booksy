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
            $request->session()->regenerate();

            return redirect()->intended(route('owner.dashboard'));
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
