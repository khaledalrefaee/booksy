<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Owner "login as company" for support. The owner keeps their own session
 * (guards are independent); the session marker drives the red banner and
 * the return path, and every start/stop lands in the audit log.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Company $company): RedirectResponse
    {
        OwnerAudit::record('company.impersonate-start', $company);

        Auth::guard('company')->login($company);
        $request->session()->put('impersonator_owner_id', Auth::guard('owner')->id());

        return redirect()->route('company.dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        if (! $request->session()->has('impersonator_owner_id')) {
            return redirect()->route('owner.dashboard');
        }

        $company = Auth::guard('company')->user();

        if ($company !== null) {
            OwnerAudit::record('company.impersonate-stop', $company);
        }

        Auth::guard('company')->logout();
        $request->session()->forget('impersonator_owner_id');

        return $company !== null
            ? redirect()->route('owner.companies.show', $company)
            : redirect()->route('owner.companies.index');
    }
}
