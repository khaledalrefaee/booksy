<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBranchRequest;
use App\Http\Requests\Owner\StoreBranchWorkingHoursRequest;
use App\Http\Requests\Owner\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\BranchWorkingHour;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::query()
            ->with('company')
            ->orderBy('sort_order')
            ->orderByLocalizedName()
            ->get();

        return view('owner.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $companies = Company::query()->orderByLocalizedName()->get();

        return view('owner.branches.create', compact('companies'));
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $companyId = (int) $data['company_id'];
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_head_office'] = $request->boolean('is_head_office');

        $branch = Branch::query()->create($data);

        if ($data['is_head_office']) {
            Branch::query()
                ->where('company_id', $companyId)
                ->whereKeyNot($branch->id)
                ->update(['is_head_office' => false]);
        }

        return redirect()
            ->route('owner.branches.working-hours.create', $branch)
            ->with('success', __('Branch created. Now set working hours.'));
    }

    public function createWorkingHours(Branch $branch): View
    {
        $branch->load(['company', 'workingHours']);

        $weekDays = $this->weekDayLabels();
        $existingHours = $branch->workingHours->keyBy('day_of_week');

        return view('owner.branches.working-hours', compact('branch', 'weekDays', 'existingHours'));
    }

    public function storeWorkingHours(StoreBranchWorkingHoursRequest $request, Branch $branch): RedirectResponse
    {
        foreach ($request->validated('hours') as $hour) {
            $isOpen = ! empty($hour['is_open']);

            BranchWorkingHour::query()->updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'day_of_week' => $hour['day_of_week'],
                    'shift_number' => 1,
                ],
                [
                    'is_open' => $isOpen,
                    'open_time' => $isOpen ? ($hour['open_time'] ?? '09:00') : null,
                    'close_time' => $isOpen ? ($hour['close_time'] ?? '18:00') : null,
                ]
            );
        }

        return redirect()
            ->route('owner.branches.employees.create', ['branch' => $branch, 'wizard' => 1])
            ->with('success', __('Working hours saved. Now add employees.'));
    }

    public function skipWorkingHours(Branch $branch): RedirectResponse
    {
        return redirect()
            ->route('owner.branches.employees.create', ['branch' => $branch, 'wizard' => 1])
            ->with('success', __('Branch created. You can add employees or skip for now.'));
    }

    public function edit(Branch $branch): View
    {
        $branch->load('company');
        $companies = Company::query()->orderByLocalizedName()->get();

        return view('owner.branches.edit', compact('branch', 'companies'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_head_office'] = $request->boolean('is_head_office');

        $branch->update($data);

        if ($data['is_head_office']) {
            Branch::query()
                ->where('company_id', $branch->company_id)
                ->whereKeyNot($branch->id)
                ->update(['is_head_office' => false]);
        }

        return redirect()
            ->route('owner.branches.index')
            ->with('success', __('Branch updated successfully.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()
            ->route('owner.branches.index')
            ->with('success', __('Branch deleted successfully.'));
    }

    /**
     * @return array<int, string>
     */
    private function weekDayLabels(): array
    {
        return [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];
    }
}
