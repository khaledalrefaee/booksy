<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalController extends Controller
{
    private function staff()
    {
        /** @var \App\Models\Employee $employee */
        $employee = Auth::guard('staff')->user();

        return $employee;
    }

    /** Minimal landing for a signed-in employee (Phase 1). */
    public function home(): View
    {
        return view('staff.home', ['employee' => $this->staff()]);
    }

    public function showChangePassword(): View
    {
        return view('staff.auth.change-password', ['employee' => $this->staff()]);
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $employee = $this->staff();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:staff'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        // The `hashed` cast on Employee::password hashes this on save.
        $employee->forceFill([
            'password'             => $validated['password'],
            'must_change_password' => false,
        ])->save();

        return redirect()->route('staff.home')
            ->with('success', __('Password updated successfully.'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }
}
