<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
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

    private function authoriseEmployee(Employee $employee): void
    {
        abort_unless($employee->company_id === $this->company()->id, 403);
    }

    public function index(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $employees = $branch->employees()
            ->with('role')
            ->orderBy('name_en')
            ->get();

        return view('company.employees.index', compact('branch', 'employees'));
    }

    public function create(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $roles              = Role::query()->orderBy('label_en')->get();
        $serviceCategories  = ServiceCategory::where('company_id', $this->company()->id)
                                ->orderBy('sort_order')->get();

        return view('company.employees.create', compact('branch', 'roles', 'serviceCategories'));
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'name_en'              => ['required', 'string', 'max:255'],
            'name_ar'              => ['nullable', 'string', 'max:255'],
            'email'                => ['nullable', 'email', 'unique:employees,email'],
            'phone'                => ['nullable', 'string', 'max:30'],
            'role_id'              => ['required', 'exists:roles,id'],
            'password'             => ['required', 'string', 'min:8'],
            'bio'                  => ['nullable', 'string', 'max:1000'],
            'is_active'            => ['nullable', 'boolean'],
            'service_category_ids' => ['nullable', 'array'],
            'service_category_ids.*'=> ['exists:service_categories,id'],
        ]);

        $employee = $branch->employees()->create([
            'company_id' => $this->company()->id,
            'name_en'    => $data['name_en'],
            'name_ar'    => $data['name_ar'] ?? null,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'role_id'    => $data['role_id'],
            'password'   => Hash::make($data['password']),
            'bio'        => $data['bio'] ?? null,
            'is_active'  => $request->boolean('is_active'),
        ]);

        // Sync service categories (what the employee can do)
        $employee->serviceCategories()->sync($data['service_category_ids'] ?? []);

        return redirect()
            ->route('company.branches.employees.index', $branch)
            ->with('success', __('Employee created successfully.'));
    }

    public function edit(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        $roles             = Role::query()->orderBy('label_en')->get();
        $serviceCategories = ServiceCategory::where('company_id', $this->company()->id)
                               ->orderBy('sort_order')->get();
        $selectedCatIds    = $employee->serviceCategories()->pluck('service_categories.id')->toArray();

        return view('company.employees.edit', [
            'employee'          => $employee,
            'branch'            => $employee->branch,
            'roles'             => $roles,
            'serviceCategories' => $serviceCategories,
            'selectedCatIds'    => $selectedCatIds,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'name_en'               => ['required', 'string', 'max:255'],
            'name_ar'               => ['nullable', 'string', 'max:255'],
            'email'                 => ['nullable', 'email', "unique:employees,email,{$employee->id}"],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'role_id'               => ['required', 'exists:roles,id'],
            'password'              => ['nullable', 'string', 'min:8'],
            'bio'                   => ['nullable', 'string', 'max:1000'],
            'is_active'             => ['nullable', 'boolean'],
            'service_category_ids'  => ['nullable', 'array'],
            'service_category_ids.*'=> ['exists:service_categories,id'],
        ]);

        $updateData = [
            'name_en'   => $data['name_en'],
            'name_ar'   => $data['name_ar'] ?? null,
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'role_id'   => $data['role_id'],
            'bio'       => $data['bio'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $employee->update($updateData);

        // Sync service categories
        $employee->serviceCategories()->sync($data['service_category_ids'] ?? []);

        return redirect()
            ->route('company.branches.employees.index', $employee->branch)
            ->with('success', __('Employee updated successfully.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $branch = $employee->branch;
        $employee->delete();

        return redirect()
            ->route('company.branches.employees.index', $branch)
            ->with('success', __('Employee deleted successfully.'));
    }
}
