<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
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

    public function index(Request $request): View
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $branchId = $request->get('branch_id', $branches->first()?->id);
        $date     = $request->get('date', today()->toDateString());
        $dateObj  = Carbon::parse($date);
        $dayOfWeek = $dateObj->dayOfWeek;

        $branch = $branches->firstWhere('id', $branchId);

        $employees = Employee::where('company_id', $company->id)
            ->where(fn($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->where('is_active', true)
            ->with(['workingHours' => fn($q) => $q->where('day_of_week', $dayOfWeek)])
            ->get();

        $records = AttendanceRecord::where('branch_id', $branchId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $stats = ['present' => 0, 'late' => 0, 'absent' => 0, 'day_off' => 0, 'total_working' => 0];

        $employeeData = $employees->map(function ($emp) use ($records, $dayOfWeek, &$stats) {
            $record   = $records->get($emp->id);
            $schedule = $emp->workingHours->first();
            $isWorkingDay = $schedule && $schedule->is_working;

            if ($record) {
                $stats[$record->status === 'on_time' ? 'present' : $record->status]++;
                if (in_array($record->status, ['on_time', 'late'])) $stats['present']++;
            } elseif (!$isWorkingDay) {
                $stats['day_off']++;
            }

            if ($isWorkingDay) $stats['total_working']++;

            return [
                'employee' => $emp,
                'record'   => $record,
                'schedule' => $schedule,
                'is_working_day' => $isWorkingDay,
            ];
        });

        $stats['present'] = $records->whereIn('status', ['on_time', 'late'])->count();
        $stats['late']    = $records->where('status', 'late')->count();
        $stats['absent']  = $records->where('status', 'absent')->count();
        $stats['pct']     = $stats['total_working'] > 0
            ? round($stats['present'] / $stats['total_working'] * 100)
            : 0;

        return view('company.attendance.index', compact(
            'branches', 'branchId', 'branch', 'date', 'dateObj',
            'employeeData', 'stats'
        ));
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
        $schedule = EmployeeWorkingHour::where('employee_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $status = 'on_time';
        $lateMinutes = 0;
        $scheduledStart = $schedule?->start_time;
        $scheduledEnd   = $schedule?->end_time;

        if ($schedule && !$schedule->is_working) {
            $status = 'day_off';
        } elseif ($scheduledStart) {
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

        $attendanceRecord->update([
            'check_out'          => now(),
            'check_out_lat'      => $data['latitude'],
            'check_out_lng'      => $data['longitude'],
            'check_out_distance' => $distance,
        ]);

        return back()->with('success', __('Check-out registered successfully'));
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
        $branchId = $request->get('branch_id', $branches->first()?->id);
        $month    = $request->get('month', now()->format('Y-m'));

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

        $report = $employees->map(function ($emp) use ($records, $from, $to) {
            $empRecords = $records->get($emp->id, collect());
            $workingDaysMap = $emp->workingHours->keyBy('day_of_week');

            $totalWorkingDays = 0;
            $cursor = $from->copy();
            while ($cursor->lte($to) && $cursor->lte(today())) {
                $wh = $workingDaysMap->get($cursor->dayOfWeek);
                if ($wh && $wh->is_working) $totalWorkingDays++;
                $cursor->addDay();
            }

            $present = $empRecords->whereIn('status', ['on_time', 'late'])->count();
            $late    = $empRecords->where('status', 'late')->count();
            $absent  = $empRecords->where('status', 'absent')->count();
            $avgLate = $late > 0 ? round($empRecords->where('status', 'late')->avg('late_minutes')) : 0;
            $pct     = $totalWorkingDays > 0 ? round($present / $totalWorkingDays * 100) : 0;

            return [
                'employee'     => $emp,
                'working_days' => $totalWorkingDays,
                'present'      => $present,
                'late'         => $late,
                'absent'       => $absent,
                'avg_late'     => $avgLate,
                'pct'          => min($pct, 100),
            ];
        });

        return view('company.attendance.report', compact(
            'branches', 'branchId', 'month', 'report'
        ));
    }
}
