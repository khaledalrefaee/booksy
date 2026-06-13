<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeductionController extends Controller
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

    public function index(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        $deductions = $employee->deductions()
            ->with('recordedBy')
            ->orderByDesc('deduction_date')
            ->get();

        $totalDeducted = $deductions->where('is_sick_leave', false)->sum('amount');

        return view('company.employees.deductions.index', compact('employee', 'deductions', 'totalDeducted'));
    }

    public function create(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        // Staff who can record deductions (active employees of same company)
        $recorders = $this->company()->employees()
            ->where('id', '!=', $employee->id)
            ->where('is_active', true)
            ->orderBy('name_en')
            ->get();

        return view('company.employees.deductions.create', compact('employee', 'recorders'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'type'                    => ['required', 'in:absence,tardiness,other'],
            'is_sick_leave'           => ['nullable', 'boolean'],
            'deduction_date'          => ['required', 'date'],
            'amount'                  => ['nullable', 'numeric', 'min:0'],
            'hours'                   => ['nullable', 'numeric', 'min:0'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
            'recorded_by_employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $employee->deductions()->create([
            'type'                    => $data['type'],
            'is_sick_leave'           => $request->boolean('is_sick_leave'),
            'deduction_date'          => $data['deduction_date'],
            'amount'                  => $data['amount'] ?? null,
            'hours'                   => $data['hours'] ?? null,
            'notes'                   => $data['notes'] ?? null,
            'recorded_by_employee_id' => $data['recorded_by_employee_id'] ?? null,
        ]);

        return redirect()
            ->route('company.employees.deductions.index', $employee)
            ->with('success', __('Deduction recorded successfully.'));
    }

    public function destroy(EmployeeDeduction $deduction): RedirectResponse
    {
        abort_unless($deduction->employee->company_id === $this->company()->id, 403);

        $employee = $deduction->employee;
        $deduction->delete();

        return redirect()
            ->route('company.employees.deductions.index', $employee)
            ->with('success', __('Deduction deleted.'));
    }
}
