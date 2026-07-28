<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyHoliday;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HolidayController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $year = (int) $request->get('year', now()->year);
        if ($year < 2020 || $year > 2100) {
            $year = now()->year;
        }

        $holidays = CompanyHoliday::where('company_id', $company->id)
            ->whereYear('start_date', $year)
            ->orderBy('start_date')
            ->get();

        $totalDays = $holidays->sum(fn ($h) => $h->daysCount());

        return view('company.holidays.index', compact('holidays', 'year', 'totalDays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date', 'gte:start_date'],
            'is_paid'    => ['nullable', 'boolean'],
        ]);

        $holiday = CompanyHoliday::create([
            'company_id' => $this->company()->id,
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'] ?? $data['start_date'],
            'is_paid'    => $request->boolean('is_paid', true),
        ]);

        Auditor::log("Added holiday: {$holiday->name} ({$holiday->start_date->format('d/m/Y')})", $holiday);

        return back()->with('success', __('Holiday added.'));
    }

    public function destroy(CompanyHoliday $holiday): RedirectResponse
    {
        abort_unless($holiday->company_id === $this->company()->id, 403);

        Auditor::log("Deleted holiday: {$holiday->name}", $holiday);
        $holiday->delete();

        return back()->with('success', __('Holiday deleted.'));
    }
}
