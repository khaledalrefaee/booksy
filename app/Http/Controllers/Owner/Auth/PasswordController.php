<?php

namespace App\Http\Controllers\Owner\Auth;

use App\Http\Controllers\Controller;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Owner-guard password change: serves both the first-login forced change
 * (must_change_password) and a voluntary change from the profile.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('owner.auth.change-password', [
            'owner' => Auth::guard('owner')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $owner = Auth::guard('owner')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:owner'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        // The `hashed` cast on Owner::password hashes this on save.
        $owner->forceFill([
            'password'             => $validated['password'],
            'must_change_password' => false,
        ])->save();

        OwnerAudit::record('owner.password.changed', $owner, label: $owner->name);

        return redirect()->route($owner->homeRoute())
            ->with('success', __('Password updated successfully.'));
    }
}
