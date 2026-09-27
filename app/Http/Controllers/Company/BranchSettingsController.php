<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Support\BranchSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Branch Settings — the clock and booking window of one branch (timezone,
 * clock style, slot interval, min/max advance booking, calendar week start).
 * Customer-facing rules (online booking, same-day, cancel / reschedule) live
 * in the Booking & Cancellation Policy, not here.
 *
 * Business hours are deliberately not edited here: they live in
 * branch_working_hours (WorkingHoursController) and this page only
 * summarises them and links there.
 */
class BranchSettingsController extends Controller
{
    private function company(): Company
    {
        /** @var Company */
        return Auth::guard('company')->user();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    public function edit(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $branch->load('workingHours');

        return view('company.branches.settings', [
            'branch'         => $branch,
            'timezoneGroups' => BranchSettings::timezoneGroups(),
            'businessHours'  => $this->businessHoursSummary($branch),
            'otherBranches'  => $this->company()->branches()
                ->whereKeyNot($branch->id)
                ->orderBy('sort_order')
                ->get(['id', 'name_en', 'name_ar', 'timezone']),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate(BranchSettings::rules(), [], [
            'timezone'              => __('Time zone'),
            'time_format'           => __('Time format'),
            'appointment_interval'  => __('Appointment interval'),
            'min_booking_notice'    => __('Minimum advance booking'),
            'max_booking_days'      => __('Maximum advance booking'),
            'first_day_of_week'     => __('First day of week'),
        ]);

        $branch->update($data);

        return redirect()
            ->route('company.branches.settings.edit', $branch)
            ->with('success', __('Branch settings saved.'));
    }

    /**
     * Earliest opening / latest closing across the week, from the branch's
     * working hours (the single source of business hours).
     *
     * @return array{open:?string, close:?string, open_days:int}
     */
    private function businessHoursSummary(Branch $branch): array
    {
        $open = $branch->workingHours
            ->where('is_open', true)
            ->filter(fn ($h) => $h->open_time && $h->close_time);

        return [
            'open'      => $open->min('open_time'),
            'close'     => $open->max('close_time'),
            'open_days' => $open->pluck('day_of_week')->unique()->count(),
        ];
    }
}
