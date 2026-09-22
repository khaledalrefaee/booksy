<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchWorkingHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class WorkingHoursController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

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

    public function edit(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $branch->load('workingHours');
        $weekDays = $this->weekDayLabels();

        $existingHours = [];
        foreach ($branch->workingHours as $wh) {
            $existingHours[$wh->day_of_week][$wh->shift_number] = $wh;
        }

        return view('company.branches.working-hours', compact('branch', 'weekDays', 'existingHours'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $validator = Validator::make($request->all(), [
            'hours'                        => ['required', 'array'],
            'hours.*.day_of_week'          => ['required', 'integer', 'between:0,6'],
        ]);

        // Reject illogical time ranges (end must be strictly after start) so bad
        // hours can never be saved silently — for every open day, and both shifts.
        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('hours', []) as $dayKey => $hour) {
                if (empty($hour['is_open'])) {
                    continue;
                }

                $open  = $hour['open_time']  ?? null;
                $close = $hour['close_time'] ?? null;
                if ($open && $close && $close <= $open) {
                    $validator->errors()->add(
                        "hours.$dayKey.close_time",
                        __('The end time must be after the start time.')
                    );
                }

                if (! empty($hour['shift2_enabled'])) {
                    $s2open  = $hour['shift2_open_time']  ?? null;
                    $s2close = $hour['shift2_close_time'] ?? null;

                    if ($s2open && $s2close && $s2close <= $s2open) {
                        $validator->errors()->add(
                            "hours.$dayKey.shift2_close_time",
                            __('The end time must be after the start time.')
                        );
                    }

                    if ($close && $s2open && $s2open < $close) {
                        $validator->errors()->add(
                            "hours.$dayKey.shift2_open_time",
                            __('Shift 2 must start after shift 1 ends.')
                        );
                    }
                }
            }
        });

        $validator->validate();

        foreach ($request->input('hours', []) as $hour) {
            $isOpen = ! empty($hour['is_open']);
            $day    = (int) $hour['day_of_week'];

            BranchWorkingHour::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'day_of_week' => $day, 'shift_number' => 1],
                [
                    'is_open'    => $isOpen,
                    'open_time'  => $isOpen ? ($hour['open_time']  ?? '09:00') : null,
                    'close_time' => $isOpen ? ($hour['close_time'] ?? '18:00') : null,
                ]
            );

            $shift2Enabled = $isOpen && ! empty($hour['shift2_enabled']);

            if ($shift2Enabled) {
                BranchWorkingHour::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'day_of_week' => $day, 'shift_number' => 2],
                    [
                        'is_open'    => true,
                        'open_time'  => $hour['shift2_open_time']  ?? '14:00',
                        'close_time' => $hour['shift2_close_time'] ?? '22:00',
                    ]
                );
            } else {
                BranchWorkingHour::query()
                    ->where('branch_id', $branch->id)
                    ->where('day_of_week', $day)
                    ->where('shift_number', 2)
                    ->delete();
            }
        }

        return redirect()
            ->route('company.branches.working-hours.edit', $branch)
            ->with('success', __('Working hours saved successfully.'));
    }
}
