<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyOnboarding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /** Persist "guided tour seen" so it never replays automatically on any device. */
    public function tourComplete(): JsonResponse
    {
        $this->state()->update(['tour_completed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Hide the setup checklist card (cross-device). */
    public function dismiss(): RedirectResponse
    {
        $this->state()->update(['dismissed_at' => now()]);

        return back();
    }

    private function state(): CompanyOnboarding
    {
        return CompanyOnboarding::firstOrCreate([
            'company_id' => Auth::guard('company')->id(),
        ]);
    }
}
