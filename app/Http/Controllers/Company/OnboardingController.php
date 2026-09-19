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

    /**
     * Ask the platform to review & publish the business. Approval stays the
     * owner's decision — this only flags the account as ready.
     */
    public function submitForReview(): RedirectResponse
    {
        /** @var \App\Models\Company $company */
        $company = Auth::guard('company')->user();

        if ($company->isPublished()) {
            return back()->with('status', __('Your business is already live.'));
        }

        if ($company->submitted_for_review_at !== null) {
            return back()->with('status', __('Your business is already awaiting review.'));
        }

        if (! $company->submitForReview()) {
            return back()->with('error', __('Please finish the required setup steps before submitting for review.'));
        }

        return back()->with('status', __('✅ Your business has been submitted for review. We will approve it shortly.'));
    }

    private function state(): CompanyOnboarding
    {
        return CompanyOnboarding::firstOrCreate([
            'company_id' => Auth::guard('company')->id(),
        ]);
    }
}
