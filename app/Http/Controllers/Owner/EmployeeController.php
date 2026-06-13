<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Http\Requests\Owner\StoreEmployeesRequest;
use App\Http\Requests\Owner\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeWorkingHour;
use App\Models\Role;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use ResolvesOwnerCompany;

    public function index(Request $request, Branch $branch): View
    {
        $this->authorizeBranch($branch);

        $q         = trim($request->input('q', ''));
        $sortField = in_array($request->input('sort'), ['name', 'created_at']) ? $request->input('sort') : 'name';
        $sortDir   = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $isActive  = $request->input('is_active', '');

        $query = $branch->employees();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        if ($sortField === 'name') {
            $query->orderByLocalizedName();
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $employees = $query->paginate(15)->withQueryString();

        return view('owner.employees.index', [
            'branch'    => $branch,
            'employees' => $employees,
            'q'         => $q,
            'sortField' => $sortField,
            'sortDir'   => $sortDir,
            'isActive'  => $isActive,
        ]);
    }

    public function create(Branch $branch): View
    {
        $this->authorizeBranch($branch);

        return view('owner.employees.create', [
            'branch' => $branch,
            'wizard' => $this->isWizardStep($branch),
        ]);
    }

    public function store(StoreEmployeesRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        $defaultRole = Role::where('slug', '!=', 'company_owner')->orderBy('id')->first();

        foreach ($request->validated('employees') as $index => $row) {
            $imageFile = $request->file("employees.$index.image");
            $imagePath = $imageFile ? $imageFile->store('employees/images', 'public') : null;

            $employee = $branch->employees()->create([
                'company_id' => $branch->company_id,
                'name_en'    => $row['name_en'],
                'name_ar'    => $row['name_ar'] ?? null,
                'phone'      => $row['phone'] ?? null,
                'email'      => $row['email'] ?? null,
                'role_id'    => $defaultRole?->id ?? 1,
                'password'   => $row['password'],
                'bio'        => $row['bio'] ?? null,
                'image'      => $imagePath,
                'is_active'  => ! empty($row['is_active']),
            ]);

            // Save working hours per employee
            $hours = $request->input("employees.$index.working_hours", []);
            $this->syncWorkingHours($employee, $hours);

            // Save social links per employee
            $links = $request->input("employees.$index.social_links", []);
            SocialLink::syncFor($employee, $links);
        }

        $count   = count($request->validated('employees'));
        $message = $count === 1
            ? __('Employee created successfully.')
            : __(':count employees created successfully.', ['count' => $count]);

        if ($request->boolean('wizard')) {
            return redirect()->route('owner.branches.index')->with('success', $message);
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

        $workingHours = $employee->workingHours()->get()->keyBy('day_of_week');
        $socialLinks  = $employee->socialLinks()->get()->keyBy('platform');

        return view('owner.employees.edit', [
            'branch'       => $employee->branch,
            'employee'     => $employee,
            'workingHours' => $workingHours,
            'socialLinks'  => $socialLinks,
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

        if ($request->hasFile('image')) {
            if ($employee->image) {
                Storage::disk('public')->delete($employee->image);
            }
            $data['image'] = $request->file('image')->store('employees/images', 'public');
        } else {
            unset($data['image']);
        }

        $employee->update($data);

        $this->syncWorkingHours($employee, $request->input('working_hours', []));

        // Sync social links
        SocialLink::syncFor($employee, $request->input('social_links', []));

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

    private function syncWorkingHours(Employee $employee, array $hours): void
    {
        foreach (range(0, 6) as $day) {
            $row       = $hours[$day] ?? [];
            $isWorking = ! empty($row['is_working']);
            $employee->workingHours()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'is_working' => $isWorking,
                    'start_time' => $isWorking ? ($row['start_time'] ?? null) : null,
                    'end_time'   => $isWorking ? ($row['end_time'] ?? null) : null,
                ]
            );
        }
    }

    private function isWizardStep(Branch $branch): bool
    {
        return request()->boolean('wizard');
    }
}
