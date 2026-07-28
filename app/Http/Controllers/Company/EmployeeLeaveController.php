<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeLeaveController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authoriseEmployee(Employee $employee): void
    {
        abort_unless($employee->company_id === $this->company()->id, 403);
    }

    private function authoriseLeave(EmployeeLeave $leave): void
    {
        abort_unless($leave->company_id === $this->company()->id, 403);
    }

    public function index(Request $request): View
    {
        $company = $this->company();

        $status     = in_array($request->get('status'), ['pending', 'approved', 'rejected']) ? $request->get('status') : null;
        $employeeId = $request->integer('employee_id') ?: null;
        $branchId   = $request->integer('branch_id') ?: null;
        $month      = $request->get('month');
        if ($month && ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month)) {
            $month = null;
        }

        $base = EmployeeLeave::where('company_id', $company->id)
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))
            ->when($month, function ($q) use ($month) {
                $from = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
                $to   = $from->copy()->endOfMonth();
                $q->where('start_date', '<=', $to->toDateString())
                  ->where('end_date', '>=', $from->toDateString());
            });

        // Tab counts respect the employee/branch/month filters (not the status tab itself)
        $counts = [
            'all'      => (clone $base)->count(),
            'pending'  => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $leaves = (clone $base)
            ->with('employee.leaves')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        // Remaining annual-leave balance per employee on this page (for over-balance warnings)
        $balances = collect($leaves->items())
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn (Employee $emp) => [$emp->id => $emp->annualLeaveRemaining()]);

        $branches      = $company->branches()->orderBy('sort_order')->get();
        $employeeList  = $company->employees()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_ar']);

        return view('company.employee-leaves.index', compact(
            'leaves', 'balances', 'counts', 'branches', 'employeeList',
            'status', 'employeeId', 'branchId', 'month'
        ));
    }

    /** Monthly team calendar: who is on leave, and when. */
    public function calendar(Request $request): View
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $branchId = $request->get('branch_id', session('team.branch_id'));
        if ($branchId && ! $branches->contains('id', $branchId)) {
            $branchId = null;
        }
        if ($request->has('branch_id')) {
            session(['team.branch_id' => $branchId]);
        }

        [$month, $year] = $this->safeMonthYear($request);
        $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        $leaves = EmployeeLeave::with('employee')
            ->where('company_id', $company->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_date', '<=', $monthEnd->toDateString())
            ->where('end_date', '>=', $monthStart->toDateString())
            ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))
            ->orderBy('start_date')
            ->get();

        // Map each calendar day => leaves covering it
        $byDay = [];
        foreach ($leaves as $leave) {
            $from = $leave->start_date->greaterThan($monthStart) ? $leave->start_date : $monthStart;
            $to   = $leave->end_date->lessThan($monthEnd) ? $leave->end_date : $monthEnd;
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $byDay[$d->toDateString()][] = $leave;
            }
        }

        return view('company.employee-leaves.calendar', compact(
            'branches', 'branchId', 'month', 'year', 'monthStart', 'monthEnd', 'leaves', 'byDay'
        ));
    }

    public function create(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        $employee->load('leaves');
        $annualUsed      = $employee->annualLeaveUsed();
        $annualRemaining = $employee->annualLeaveRemaining();

        return view('company.employee-leaves.create', compact('employee', 'annualUsed', 'annualRemaining'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $isHourly = $request->boolean('is_hourly');

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => [$isHourly ? 'nullable' : 'required', 'date', 'gte:start_date'],
            'type'       => ['required', 'in:' . implode(',', array_keys(EmployeeLeave::LEAVE_TYPES))],
            'is_hourly'  => ['nullable', 'boolean'],
            'start_hour' => [$isHourly ? 'required' : 'nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'end_hour'   => [$isHourly ? 'required' : 'nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'reason'     => ['nullable', 'string', 'max:500'],
            'deduction_amount'   => ['nullable', 'numeric', 'min:0'],
            'deduction_currency' => ['nullable', 'in:' . implode(',', array_keys(config('booksy.currencies', [])))],
        ]);

        if ($isHourly && substr($data['end_hour'], 0, 5) <= substr($data['start_hour'], 0, 5)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_hour' => __('End time must be after start time.'),
            ]);
        }

        $deductionAmount = (float) ($data['deduction_amount'] ?? 0);

        $employee->leaves()->create([
            'company_id' => $this->company()->id,
            'start_date' => $data['start_date'],
            'end_date'   => $isHourly ? $data['start_date'] : $data['end_date'],
            'type'       => $data['type'],
            'is_hourly'  => $isHourly,
            'start_hour' => $isHourly ? substr($data['start_hour'], 0, 5) : null,
            'end_hour'   => $isHourly ? substr($data['end_hour'], 0, 5) : null,
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
            'deduction_amount'   => $deductionAmount > 0 ? $deductionAmount : null,
            'deduction_currency' => $deductionAmount > 0
                ? ($data['deduction_currency'] ?? config('booksy.default_currency', 'SYP'))
                : null,
        ]);

        return redirect()
            ->route('company.employee-leaves.index')
            ->with('success', __('Leave request submitted.'));
    }

    public function updateStatus(Request $request, EmployeeLeave $employeeLeave): RedirectResponse
    {
        $this->authoriseLeave($employeeLeave);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $remainingBefore = $employeeLeave->employee->annualLeaveRemaining();
        $exceedsBalance  = $data['status'] === 'approved'
            && $employeeLeave->type === 'annual'
            && $employeeLeave->daysCount() > $remainingBefore;

        $employeeLeave->update($data);

        $this->syncLeaveDeduction($employeeLeave);

        // WhatsApp decision notice — must never break the update itself
        try {
            $remainingAfter = $data['status'] === 'approved' && $employeeLeave->type === 'annual'
                ? $remainingBefore - $employeeLeave->daysCount()
                : null;
            app(\App\Services\WhatsappService::class)->sendLeaveDecision($employeeLeave->fresh('employee'), $remainingAfter);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Leave decision WhatsApp failed: {$e->getMessage()}");
        }

        $redirect = redirect()
            ->route('company.employee-leaves.index')
            ->with('success', __('Leave request updated.'));

        if ($exceedsBalance) {
            $redirect->with('warning', __(':name has exceeded their annual leave balance (:remaining days remaining before this leave).', [
                'name'      => $employeeLeave->employee->localizedName(),
                'remaining' => max(0, $remainingBefore),
            ]));
        }

        return $redirect;
    }

    public function destroy(EmployeeLeave $employeeLeave): RedirectResponse
    {
        $this->authoriseLeave($employeeLeave);

        // Remove the salary deduction that was created with the approval
        $employeeLeave->deduction?->delete();
        $employeeLeave->delete();

        return redirect()
            ->route('company.employee-leaves.index')
            ->with('success', __('Leave request deleted.'));
    }

    /**
     * Keep the linked salary deduction in step with the leave's status:
     * approved + amount set → ensure an EmployeeDeduction exists,
     * anything else → remove it.
     */
    private function syncLeaveDeduction(EmployeeLeave $leave): void
    {
        // Unpaid leave with no manual amount → auto-compute from the day rate
        if ($leave->status === 'approved'
            && $leave->type === 'unpaid'
            && (float) $leave->deduction_amount <= 0
            && ($rate = $leave->employee->dailyRate())) {
            $amount = $leave->is_hourly
                ? round(($rate['amount'] / 8) * $leave->hoursCount(), 2)
                : round($rate['amount'] * $leave->daysCount(), 2);

            if ($amount > 0) {
                $leave->forceFill([
                    'deduction_amount'   => $amount,
                    'deduction_currency' => $rate['currency'],
                ])->save();
            }
        }

        $wantsDeduction = $leave->status === 'approved' && (float) $leave->deduction_amount > 0;

        if ($wantsDeduction && ! $leave->deduction_id) {
            $typeLabel = __(($leave->typeMeta())['label_key']);
            $period    = $leave->is_hourly
                ? $leave->start_date->format('d/m/Y') . " {$leave->start_hour}–{$leave->end_hour}"
                : $leave->start_date->format('d/m/Y') . ' – ' . $leave->end_date->format('d/m/Y');

            $deduction = $leave->employee->deductions()->create([
                'type'           => 'absence',
                'is_sick_leave'  => false,
                'deduction_date' => $leave->start_date,
                'amount'         => $leave->deduction_amount,
                'currency'       => $leave->deduction_currency ?? config('booksy.default_currency', 'SYP'),
                'hours'          => $leave->is_hourly ? $leave->hoursCount() : null,
                'notes'          => __('Leave deduction (:type) — :period', ['type' => $typeLabel, 'period' => $period]),
            ]);

            $leave->update(['deduction_id' => $deduction->id]);
        } elseif (! $wantsDeduction && $leave->deduction_id) {
            $leave->deduction?->delete();
            $leave->update(['deduction_id' => null]);
        }
    }
}
