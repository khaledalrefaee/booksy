<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeWorkingHour;
use App\Models\StaffNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    /**
     * Resolve the branch filter: explicit query param wins (and is remembered),
     * otherwise fall back to the branch remembered in the session, then the first branch.
     */
    private function rememberedBranchId(Request $request, $branches)
    {
        $branchId = $request->get('branch_id', session('team.branch_id'));

        if (! $branches->contains('id', $branchId)) {
            $branchId = $branches->first()?->id;
        }

        session(['team.branch_id' => $branchId]);

        return $branchId;
    }

    public function index(Request $request): View
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $branchId = $this->rememberedBranchId($request, $branches);

        try {
            $dateObj = Carbon::parse($request->get('date', today()->toDateString()));
        } catch (\Throwable) {
            $dateObj = today();
        }
        $date      = $dateObj->toDateString();
        $dayOfWeek = $dateObj->dayOfWeek;

        $branch = $branches->firstWhere('id', $branchId);

        $employees = Employee::where('company_id', $company->id)
            ->where(fn($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->where('is_active', true)
            ->with(['compensation', 'workingHours' => fn($q) => $q->where('day_of_week', $dayOfWeek)])
            ->get();

        $records = AttendanceRecord::where('branch_id', $branchId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        // Public holiday covering this date (applies to the whole company)
        $holiday = CompanyHoliday::coveringDate($company->id, $date);

        // Approved leaves covering this date → shown as "On leave" instead of absent
        $dayLeaves = EmployeeLeave::where('company_id', $company->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        // Tardiness/absence deductions already recorded on this date (to hide the suggest buttons)
        $dayDeductions = \App\Models\EmployeeDeduction::whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('type', ['tardiness', 'absence'])
            ->whereDate('deduction_date', $date)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => $group->pluck('type')->flip());

        // Behavioral alerts: employees late 3+ times in the last 7 days
        $lateAlerts = AttendanceRecord::where('branch_id', $branchId)
            ->where('status', 'late')
            ->where('date', '>=', today()->subDays(7))
            ->selectRaw('employee_id, COUNT(*) as times, ROUND(AVG(late_minutes)) as avg_late')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get()
            ->map(function ($row) use ($employees) {
                $row->employee = $employees->firstWhere('id', $row->employee_id)
                    ?? Employee::find($row->employee_id);
                return $row;
            })
            ->filter(fn ($row) => $row->employee);

        $stats = ['present' => 0, 'late' => 0, 'absent' => 0, 'day_off' => 0, 'on_leave' => 0, 'total_working' => 0];

        $employeeData = $employees->map(function ($emp) use ($records, $dayLeaves, $holiday, &$stats) {
            $record   = $records->get($emp->id);
            $leave    = $dayLeaves->get($emp->id);
            $shifts   = $emp->workingHours->where('is_working', true)->sortBy('shift_number')->values();
            $schedule = $emp->workingHours->sortBy('shift_number')->first();
            $isWorkingDay = $shifts->isNotEmpty() && ! $holiday;

            // Full-day approved leave overrides the working day; hourly permissions don't
            $onLeaveAllDay = $leave && ! $leave->is_hourly;

            if ($onLeaveAllDay && ! $record) {
                $stats['on_leave']++;
            } elseif (! $isWorkingDay && ! $record) {
                $stats['day_off']++;
            }

            if ($isWorkingDay && ! $onLeaveAllDay) $stats['total_working']++;

            return [
                'employee' => $emp,
                'record'   => $record,
                'leave'    => $leave,
                'on_leave_all_day' => $onLeaveAllDay,
                'schedule' => $schedule,
                'shifts'   => $shifts,
                'is_working_day' => $isWorkingDay,
            ];
        });

        // Suggested deduction per record: tardiness for late check-ins, a full day
        // rate for absences (null when already deducted or not computable)
        $employeeData = $employeeData->map(function ($item) use ($dayDeductions) {
            $record   = $item['record'];
            $empTypes = $dayDeductions->get($item['employee']->id, collect());
            $item['suggested_deduction'] = null;
            $item['already_deducted']    = false;

            if ($record && $record->status === 'late' && $record->late_minutes > 0) {
                if ($empTypes->has('tardiness')) {
                    $item['already_deducted'] = true;
                } elseif ($sug = $this->suggestLateDeduction($item['employee'], $record)) {
                    $item['suggested_deduction'] = $sug + ['type' => 'tardiness'];
                }
            } elseif ($record && $record->status === 'absent') {
                if ($empTypes->has('absence')) {
                    $item['already_deducted'] = true;
                } elseif ($rate = $item['employee']->dailyRate()) {
                    $item['suggested_deduction'] = $rate + [
                        'type'       => 'absence',
                        'daily_rate' => $rate['amount'],
                        'pay_period' => $item['employee']->compensation?->pay_period ?? 'monthly',
                    ];
                }
            }

            return $item;
        });

        $stats['present'] = $records->whereIn('status', ['on_time', 'late'])->count();
        $stats['late']    = $records->where('status', 'late')->count();
        $stats['absent']  = $records->where('status', 'absent')->count();
        $stats['pct']     = $stats['total_working'] > 0
            ? round($stats['present'] / $stats['total_working'] * 100)
            : 0;

        return view('company.attendance.index', compact(
            'branches', 'branchId', 'branch', 'date', 'dateObj',
            'employeeData', 'stats', 'lateAlerts', 'holiday'
        ));
    }

    /**
     * Compute a suggested tardiness deduction for a late record, based on the
     * employee's base salary broken down to a per-minute rate.
     *
     * @return array{amount: float, currency: string}|null
     */
    private function suggestLateDeduction(Employee $employee, AttendanceRecord $record): ?array
    {
        $comp = $employee->compensation;
        if (! $comp || ! in_array($comp->type, ['salary', 'mixed']) || $comp->base_amount <= 0) {
            return null;
        }

        // Daily scheduled hours from the record itself, defaulting to 8h
        $dailyHours = 8.0;
        if ($record->scheduled_start && $record->scheduled_end) {
            $mins = Carbon::parse($record->scheduled_start)->diffInMinutes(Carbon::parse($record->scheduled_end));
            if ($mins > 0) $dailyHours = $mins / 60;
        }

        $dailyRate = match ($comp->pay_period) {
            'daily'  => (float) $comp->base_amount,
            'weekly' => $comp->base_amount / 6,
            default  => $comp->base_amount / 26, // monthly, standard 26 working days
        };

        $hourlyRate = $dailyRate / $dailyHours;
        $amount     = round($hourlyRate * ($record->late_minutes / 60), 2);

        return $amount > 0
            ? [
                'amount'       => $amount,
                'currency'     => $comp->currency ?? config('booksy.default_currency', 'SYP'),
                'daily_rate'   => round($dailyRate, 2),
                'daily_hours'  => round($dailyHours, 1),
                'hourly_rate'  => round($hourlyRate, 2),
                'pay_period'   => $comp->pay_period,
                'late_minutes' => (int) $record->late_minutes,
            ]
            : null;
    }

    /** One-click: record the suggested deduction for a late (tardiness) or absent (full day) record. */
    public function storeSuggestedDeduction(AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $company = $this->company();
        abort_unless($attendanceRecord->company_id === $company->id, 403);

        $employee = $attendanceRecord->employee()->with('compensation')->first();

        $isLate   = $attendanceRecord->status === 'late' && $attendanceRecord->late_minutes > 0;
        $isAbsent = $attendanceRecord->status === 'absent';

        if (! $isLate && ! $isAbsent) {
            return back()->with('error', __('This record has nothing to deduct.'));
        }

        $type = $isLate ? 'tardiness' : 'absence';

        $exists = \App\Models\EmployeeDeduction::where('employee_id', $employee->id)
            ->where('type', $type)
            ->whereDate('deduction_date', $attendanceRecord->date)
            ->exists();
        if ($exists) {
            return back()->with('error', __('A deduction of this type already exists for this day.'));
        }

        $suggestion = $isLate
            ? $this->suggestLateDeduction($employee, $attendanceRecord)
            : $employee->dailyRate();
        if (! $suggestion) {
            return back()->with('error', __('Cannot compute a deduction — no base salary configured for this employee.'));
        }

        $employee->deductions()->create([
            'type'           => $type,
            'is_sick_leave'  => false,
            'deduction_date' => $attendanceRecord->date,
            'amount'         => $suggestion['amount'],
            'currency'       => $suggestion['currency'],
            'hours'          => $isLate ? round($attendanceRecord->late_minutes / 60, 2) : null,
            'notes'          => $isLate
                ? __('Auto: late :min minutes on :date', [
                    'min'  => $attendanceRecord->late_minutes,
                    'date' => $attendanceRecord->date->format('d/m/Y'),
                ])
                : __('Auto: absent on :date (full day rate)', [
                    'date' => $attendanceRecord->date->format('d/m/Y'),
                ]),
        ]);

        return back()->with('success', __('Deduction recorded — :amount :currency', [
            'amount'   => number_format($suggestion['amount'], 0),
            'currency' => config("booksy.currencies.{$suggestion['currency']}.symbol", $suggestion['currency']),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'latitude'    => ['required', 'numeric'],
            'longitude'   => ['required', 'numeric'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        abort_unless($employee->company_id === $company->id, 403);

        $exists = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', today())
            ->whereNotNull('check_in')
            ->exists();
        if ($exists) {
            return back()->with('error', __('Already checked in today'));
        }

        $branch = $employee->branch;
        $distance = 0;
        $locationStatus = 'outside';

        if ($branch && $branch->latitude && $branch->longitude) {
            $distance = (int) AttendanceRecord::haversineDistance(
                (float) $data['latitude'], (float) $data['longitude'],
                (float) $branch->latitude, (float) $branch->longitude
            );
            $locationStatus = AttendanceRecord::locationStatus($distance);
        }

        $dayOfWeek = today()->dayOfWeek;
        $dayRows = EmployeeWorkingHour::where('employee_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('shift_number')
            ->get();
        $shifts = $dayRows->where('is_working', true)->values();

        $status = 'on_time';
        $lateMinutes = 0;
        $scheduledStart = null;
        $scheduledEnd   = null;

        if ($dayRows->isNotEmpty() && $shifts->isEmpty()) {
            // Schedule exists but the day is off
            $status = 'day_off';
        } elseif ($shifts->isNotEmpty()) {
            // Lateness is measured against the current/upcoming shift:
            // the first shift that hasn't ended yet, or the last shift if all have ended.
            $currentShift = $shifts->first(function ($s) {
                return now()->lt(Carbon::parse(today()->format('Y-m-d') . ' ' . $s->end_time));
            }) ?? $shifts->last();

            $scheduledStart = $currentShift->start_time;
            $scheduledEnd   = $shifts->last()->end_time;

            $expected = Carbon::parse(today()->format('Y-m-d') . ' ' . $scheduledStart);
            $diffMinutes = (int) $expected->diffInMinutes(now(), false);
            if ($diffMinutes > 0) {
                $status = 'late';
                $lateMinutes = $diffMinutes;
            }
        }

        AttendanceRecord::create([
            'employee_id'     => $employee->id,
            'branch_id'       => $employee->branch_id ?? $branch?->id,
            'company_id'      => $company->id,
            'date'            => today(),
            'check_in'        => now(),
            'scheduled_start' => $scheduledStart,
            'scheduled_end'   => $scheduledEnd,
            'status'          => $status,
            'check_in_lat'    => $data['latitude'],
            'check_in_lng'    => $data['longitude'],
            'check_in_distance' => $distance,
            'location_status' => $locationStatus,
            'late_minutes'    => $lateMinutes,
            'notes'           => $data['notes'] ?? null,
        ]);

        if ($status === 'late') {
            StaffNotification::create([
                'company_id' => $company->id,
                'branch_id'  => $employee->branch_id,
                'type'       => 'late_checkin',
                'title'      => __(':name checked in :min minutes late', [
                    'name' => $employee->name_ar ?: $employee->name_en,
                    'min'  => $lateMinutes,
                ]),
                'icon'  => '⏰',
                'color' => '#f59e0b',
                'link'  => route('company.attendance.index', ['date' => today()->format('Y-m-d')]),
            ]);
        }

        $statusMsg = match($status) {
            'late'    => __('Check-in registered — :min minutes late', ['min' => $lateMinutes]),
            'day_off' => __('Check-in registered (day off)'),
            default   => __('Check-in registered successfully'),
        };

        return back()->with('success', $statusMsg . " ({$distance}m)");
    }

    public function checkOut(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $company = $this->company();
        abort_unless($attendanceRecord->company_id === $company->id, 403);

        $data = $request->validate([
            'latitude'  => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $branch = $attendanceRecord->branch;
        $distance = 0;
        if ($branch && $branch->latitude && $branch->longitude) {
            $distance = (int) AttendanceRecord::haversineDistance(
                (float) $data['latitude'], (float) $data['longitude'],
                (float) $branch->latitude, (float) $branch->longitude
            );
        }

        // Overtime / early leave vs. the scheduled shift end
        [$overtime, $earlyLeave] = $this->overtimeAndEarlyLeave($attendanceRecord, now());

        $attendanceRecord->update([
            'check_out'           => now(),
            'check_out_lat'       => $data['latitude'],
            'check_out_lng'       => $data['longitude'],
            'check_out_distance'  => $distance,
            'overtime_minutes'    => $overtime,
            'early_leave_minutes' => $earlyLeave,
        ]);

        if ($earlyLeave >= 15) {
            $employee = $attendanceRecord->employee;
            StaffNotification::create([
                'company_id' => $company->id,
                'branch_id'  => $attendanceRecord->branch_id,
                'type'       => 'early_leave',
                'title'      => __(':name left :min minutes early', [
                    'name' => $employee->name_ar ?: $employee->name_en,
                    'min'  => $earlyLeave,
                ]),
                'icon'  => '🏃',
                'color' => '#f59e0b',
                'link'  => route('company.attendance.index', ['date' => $attendanceRecord->date->format('Y-m-d')]),
            ]);
        }

        $msg = match (true) {
            $overtime > 0   => __('Check-out registered — :min minutes overtime', ['min' => $overtime]),
            $earlyLeave > 0 => __('Check-out registered — left :min minutes early', ['min' => $earlyLeave]),
            default         => __('Check-out registered successfully'),
        };

        return back()->with('success', $msg);
    }

    /**
     * Overtime & early-leave minutes for a check-out time vs. the scheduled end.
     *
     * @return array{0: int, 1: int} [overtime, earlyLeave]
     */
    private function overtimeAndEarlyLeave(AttendanceRecord $record, Carbon $checkOut): array
    {
        if (! $record->scheduled_end) {
            return [0, 0];
        }

        $scheduledEnd = Carbon::parse($record->date->format('Y-m-d') . ' ' . $record->scheduled_end);
        $diff = (int) $scheduledEnd->diffInMinutes($checkOut, false); // + after shift end, − before

        return $diff >= 0 ? [$diff, 0] : [0, -$diff];
    }

    /**
     * Correct an attendance record: adjust check-in/out times or notes
     * (e.g. an employee who forgot to check out). Lateness, overtime and
     * early-leave are recomputed against the stored schedule.
     */
    public function update(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $company = $this->company();
        abort_unless($attendanceRecord->company_id === $company->id, 403);

        $data = $request->validate([
            'check_in'  => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'check_out' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $day = $attendanceRecord->date->format('Y-m-d');

        $checkIn  = $data['check_in'] ? Carbon::parse("{$day} " . substr($data['check_in'], 0, 5)) : null;
        $checkOut = $data['check_out'] ? Carbon::parse("{$day} " . substr($data['check_out'], 0, 5)) : null;

        if ($checkIn && $checkOut && $checkOut->lte($checkIn)) {
            return back()->with('error', __('Check-out time must be after check-in time.'));
        }

        $update = ['notes' => $data['notes'] ?? $attendanceRecord->notes];

        if ($checkIn) {
            $update['check_in'] = $checkIn;

            // Recompute lateness against the scheduled start
            $lateMinutes = 0;
            $status      = $attendanceRecord->status === 'day_off' ? 'day_off' : 'on_time';
            if ($attendanceRecord->scheduled_start && $status !== 'day_off') {
                $expected = Carbon::parse("{$day} " . $attendanceRecord->scheduled_start);
                $diff = (int) $expected->diffInMinutes($checkIn, false);
                if ($diff > 0) {
                    $status      = 'late';
                    $lateMinutes = $diff;
                }
            }
            $update['status']       = $status;
            $update['late_minutes'] = $lateMinutes;
        }

        if ($checkOut) {
            $update['check_out'] = $checkOut;
            [$update['overtime_minutes'], $update['early_leave_minutes']] =
                $this->overtimeAndEarlyLeave($attendanceRecord, $checkOut);
        }

        $attendanceRecord->update($update);

        \App\Support\Auditor::log(
            "Corrected attendance record for {$attendanceRecord->employee->localizedName()} on {$attendanceRecord->date->format('d/m/Y')}",
            $attendanceRecord
        );

        return back()->with('success', __('Attendance record updated.'));
    }

    public function markAbsent(Request $request): RedirectResponse
    {
        $company = $this->company();

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date'        => ['nullable', 'date'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        abort_unless($employee->company_id === $company->id, 403);

        $date = $data['date'] ?? today()->toDateString();

        // On approved leave → not an absence
        $onLeave = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('is_hourly', false)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
        if ($onLeave) {
            return back()->with('error', __('This employee is on an approved leave for this day — cannot mark as absent.'));
        }

        AttendanceRecord::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            [
                'branch_id'  => $employee->branch_id,
                'company_id' => $company->id,
                'status'     => 'absent',
                'notes'      => $data['notes'] ?? null,
            ]
        );

        StaffNotification::create([
            'company_id' => $company->id,
            'branch_id'  => $employee->branch_id,
            'type'       => 'absent',
            'title'      => __(':name marked as absent', ['name' => $employee->name_ar ?: $employee->name_en]),
            'body'       => $data['notes'] ?? null,
            'icon'       => '❌',
            'color'      => '#ef4444',
            'link'       => route('company.attendance.index', ['date' => $date]),
        ]);

        return back()->with('success', __('Employee marked as absent'));
    }

    public function report(Request $request): View
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $branchId = $this->rememberedBranchId($request, $branches);

        $month = $request->get('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month)) {
            $month = now()->format('Y-m');
        }

        $from = Carbon::parse($month . '-01')->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        $employees = Employee::where('company_id', $company->id)
            ->where(fn($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->where('is_active', true)
            ->with('workingHours')
            ->get();

        $records = AttendanceRecord::where('company_id', $company->id)
            ->where('branch_id', $branchId)
            ->whereBetween('date', [$from, $to])
            ->get()
            ->groupBy('employee_id');

        // Public holidays & approved leaves in the month — excluded from working days
        $holidayDates = CompanyHoliday::datesInRange($company->id, $from, $to);
        $monthLeaves  = EmployeeLeave::where('company_id', $company->id)
            ->where('status', 'approved')
            ->where('is_hourly', false)
            ->where('start_date', '<=', $to->toDateString())
            ->where('end_date', '>=', $from->toDateString())
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $report = $employees->map(function ($emp) use ($records, $from, $to, $holidayDates, $monthLeaves) {
            $empRecords = $records->get($emp->id, collect());
            $workingDaysMap = $emp->workingHours->keyBy('day_of_week');
            // No schedule configured → treat every day as a working day (same as payroll)
            $hasSchedule = $emp->workingHours->where('is_working', true)->isNotEmpty();
            $recordsByDate = $empRecords->keyBy(fn ($r) => $r->date->toDateString());

            // Dates covered by this employee's approved leaves
            $leaveDates = [];
            foreach ($monthLeaves->get($emp->id, collect()) as $leave) {
                $s = $leave->start_date->greaterThan($from) ? $leave->start_date->copy() : $from->copy();
                $e = $leave->end_date->lessThan($to) ? $leave->end_date->copy() : $to->copy();
                for ($d = $s->copy(); $d->lte($e); $d->addDay()) {
                    $leaveDates[$d->toDateString()] = true;
                }
            }

            $totalWorkingDays = 0;
            $onLeaveDays      = 0;
            $missedDays       = 0; // working days that passed with no record at all
            $cursor = $from->copy();
            while ($cursor->lte($to) && $cursor->lte(today())) {
                $key = $cursor->toDateString();
                $wh  = $workingDaysMap->get($cursor->dayOfWeek);
                $isWorkingDay = ($hasSchedule ? ($wh && $wh->is_working) : true) && ! isset($holidayDates[$key]);

                if ($isWorkingDay) {
                    if (isset($leaveDates[$key])) {
                        $onLeaveDays++;
                    } else {
                        $totalWorkingDays++;
                        // A past working day with no check-in and no absent mark = missed
                        if (! $recordsByDate->has($key) && $cursor->lt(today())) {
                            $missedDays++;
                        }
                    }
                }
                $cursor->addDay();
            }

            $present  = $empRecords->whereIn('status', ['on_time', 'late'])->count();
            $late     = $empRecords->where('status', 'late')->count();
            $absent   = $empRecords->where('status', 'absent')->count() + $missedDays;
            $avgLate  = $late > 0 ? round($empRecords->where('status', 'late')->avg('late_minutes')) : 0;
            $overtime = (int) $empRecords->sum('overtime_minutes');
            $early    = (int) $empRecords->sum('early_leave_minutes');
            $pct      = $totalWorkingDays > 0 ? round($present / $totalWorkingDays * 100) : 0;

            return [
                'employee'     => $emp,
                'working_days' => $totalWorkingDays,
                'present'      => $present,
                'late'         => $late,
                'absent'       => $absent,
                'on_leave'     => $onLeaveDays,
                'avg_late'     => $avgLate,
                'overtime_min' => $overtime,
                'early_min'    => $early,
                'pct'          => min($pct, 100),
            ];
        });

        return view('company.attendance.report', compact(
            'branches', 'branchId', 'month', 'report'
        ));
    }
}
