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

    /** Go live: flip the business to active so it appears on the marketplace. */
    public function publish(): RedirectResponse
    {
        /** @var \App\Models\Company $company */
        $company = Auth::guard('company')->user();

        if ($company->isPublished()) {
            return back()->with('status', __('Your business is already live.'));
        }

        if (! $company->publish()) {
            return back()->with('error', __('Please finish the required setup steps before publishing.'));
        }

        return back()->with('status', __('🎉 Your business is now live on GlowRez!'));
    }

    private function state(): CompanyOnboarding
    {
        return CompanyOnboarding::firstOrCreate([
            'company_id' => Auth::guard('company')->id(),
        ]);
    }
}
