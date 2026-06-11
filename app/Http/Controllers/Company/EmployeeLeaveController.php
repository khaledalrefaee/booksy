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

    public function index(): View
    {
        $leaves = EmployeeLeave::with('employee')
            ->where('company_id', $this->company()->id)
            ->orderByDesc('start_date')
            ->get();

        return view('company.employee-leaves.index', compact('leaves'));
    }

    public function create(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        return view('company.employee-leaves.create', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'gte:start_date'],
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        $employee->leaves()->create([
            'company_id' => $this->company()->id,
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
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

        $employeeLeave->update($data);

        return redirect()
            ->route('company.employee-leaves.index')
            ->with('success', __('Leave request updated.'));
    }

    public function destroy(EmployeeLeave $employeeLeave): RedirectResponse
    {
        $this->authoriseLeave($employeeLeave);
        $employeeLeave->delete();

        return redirect()
            ->route('company.employee-leaves.index')
            ->with('success', __('Leave request deleted.'));
    }
}
