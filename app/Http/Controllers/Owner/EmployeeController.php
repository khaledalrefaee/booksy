<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Http\Requests\Owner\StoreEmployeesRequest;
use App\Http\Requests\Owner\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use ResolvesOwnerCompany;

    public function index(Branch $branch): View
    {
        $this->authorizeBranch($branch);

        $employees = $branch->employees()
            ->with('role')
            ->orderByLocalizedName()
            ->get();

        return view('owner.employees.index', [
            'branch' => $branch,
            'employees' => $employees,
        ]);
    }

    public function create(Branch $branch): View
    {
        $this->authorizeBranch($branch);

        return view('owner.employees.create', [
            'branch' => $branch,
            'roles' => $this->branchAssignableRoles(),
            'wizard' => $this->isWizardStep($branch),
        ]);
    }

    public function store(StoreEmployeesRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        foreach ($request->validated('employees') as $row) {
            $branch->employees()->create([
                'company_id' => $branch->company_id,
                'name_en' => $row['name_en'],
                'name_ar' => $row['name_ar'],
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
                'role_id' => $row['role_id'],
                'password' => $row['password'],
                'bio' => $row['bio'] ?? null,
                'is_active' => ! empty($row['is_active']),
            ]);
        }

        $count = count($request->validated('employees'));
        $message = $count === 1
            ? __('Employee created successfully.')
            : __(':count employees created successfully.', ['count' => $count]);

        if ($request->boolean('wizard')) {
            return redirect()
                ->route('owner.branches.index')
                ->with('success', $message);
        }

        return redirect()
            ->route('owner.branches.employees.index', $branch)
            ->with('success', $message);
    }

    public function skipEmployees(Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        return redirect()
            ->route('owner.branches.index')
            ->with('success', __('Branch setup complete. You can add employees later.'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorizeEmployee($employee);

        return view('owner.employees.edit', [
            'branch' => $employee->branch,
            'employee' => $employee,
            'roles' => $this->branchAssignableRoles(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $employee->update($data);

        return redirect()
            ->route('owner.branches.employees.index', $employee->branch)
            ->with('success', __('Employee updated successfully.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $branch = $employee->branch;
        $employee->delete();

        return redirect()
            ->route('owner.branches.employees.index', $branch)
            ->with('success', __('Employee deleted successfully.'));
    }

    private function isWizardStep(Branch $branch): bool
    {
        return request()->boolean('wizard');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    private function branchAssignableRoles()
    {
        return Role::query()
            ->where('slug', '!=', 'company_owner')
            ->orderBy('label_en')
            ->get();
    }
}
