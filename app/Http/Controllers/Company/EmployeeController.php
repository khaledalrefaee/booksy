<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeWorkingHour;
use App\Models\EmployeeCompensation;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SocialLink;
use App\Support\Access\EmployeeAccessWriter;
use App\Support\Access\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    /** Normalise the branch-access + overrides fields for the EmployeeAccessWriter. */
    private function accessInput(Request $request): array
    {
        return [
            'access_mode'      => $request->input('access_mode', 'selected'),
            'branch_ids'       => $request->input('branch_ids', []),
            'full_access'      => $request->boolean('full_access'),
            'overrides'        => $request->input('overrides', []),
            'per_branch'       => $request->boolean('per_branch'),
            'branch_overrides' => $request->input('branch_overrides', []),
        ];
    }

    /** AJAX: live email-uniqueness check while typing in the employee form. */
    public function checkEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $email  = trim((string) $request->query('email'));
        $except = $request->query('except');

        if ($email === '') {
            return response()->json(['available' => true]);
        }

        $exists = Employee::where('email', $email)
            ->when($except, fn ($q) => $q->where('id', '!=', $except))
            ->exists();

        return response()->json(['available' => ! $exists]);
    }

    /**
     * Reject shifts whose end is not after their start, and overlapping shifts
     * on the same day. Runs after the base validate() call.
     */
    private function validateShiftTimes(Request $request): void
    {
        $errors   = [];
        $dayNames = EmployeeWorkingHour::$dayNames;
        $langKey  = app()->getLocale() === 'ar' ? 'ar' : 'en';

        foreach ($request->input('working_hours', []) as $day => $row) {
            if (empty($row['is_working'])) {
                continue;
            }

            $shifts = collect($row['shifts'] ?? [])
                ->filter(fn ($s) => ! empty($s['start_time']) && ! empty($s['end_time']))
                ->map(fn ($s) => ['start' => substr($s['start_time'], 0, 5), 'end' => substr($s['end_time'], 0, 5)])
                ->sortBy('start')
                ->values();

            if ($shifts->isEmpty() && ! empty($row['start_time']) && ! empty($row['end_time'])) {
                $shifts = collect([['start' => substr($row['start_time'], 0, 5), 'end' => substr($row['end_time'], 0, 5)]]);
            }

            $dayName = $dayNames[(int) $day][$langKey] ?? $day;

            foreach ($shifts as $i => $shift) {
                if ($shift['end'] <= $shift['start']) {
                    $errors["working_hours.$day"] = __(':day: shift end time must be after its start time.', ['day' => $dayName]);
                    break;
                }
                if ($i > 0 && $shift['start'] < $shifts[$i - 1]['end']) {
                    $errors["working_hours.$day"] = __(':day: shifts overlap each other.', ['day' => $dayName]);
                    break;
                }
            }
        }

        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public function index(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        // Employees of this branch + company-wide employees (branch_id NULL = all branches)
        $branchScope = fn () => Employee::where('company_id', $branch->company_id)
            ->where(fn ($q) => $q->where('branch_id', $branch->id)->orWhereNull('branch_id'));

        $query = $branchScope()
            ->with([
                'role', 'compensation', 'serviceCommissions',
                // Just what currentLeave() needs: approved leaves covering today
                'leaves' => fn ($q) => $q->where('status', 'approved')
                    ->whereDate('start_date', '<=', today())
                    ->whereDate('end_date', '>=', today()),
            ]);

        $employees = $query
            ->withCount(['branches'])
            ->withCount([
                'appointments as appointments_this_month' => fn($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year),
                'appointments as appointments_completed_month' => fn($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year)
                    ->where('status', 'completed'),
            ])
            ->withSum(
                ['appointments as revenue_this_month' => fn($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year)
                    ->where('status', 'completed')],
                'total_price'
            )
            ->orderBy('name_en')
            ->paginate(50)
            ->withQueryString();

        // Branch-wide counts for the header & filter chips (not just the current page)
        $totalCount    = $branchScope()->count();
        $activeCount   = $branchScope()->where('is_active', true)->count();
        $inactiveCount = $totalCount - $activeCount;
        $roles         = Role::whereIn('id', $branchScope()->whereNotNull('role_id')->distinct()->pluck('role_id'))->get();

        $alerts = $branchScope()
            ->where(function ($q) {
                $q->whereNotNull('license_expiry')
                  ->orWhereNotNull('contract_end_date');
            })
            ->get(['id', 'name_en', 'name_ar', 'license_expiry', 'contract_end_date']);

        return view('company.employees.index', compact(
            'branch', 'employees', 'alerts',
            'totalCount', 'activeCount', 'inactiveCount', 'roles'
        ));
    }

    public function create(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $roles    = Role::query()->orderBy('label_en')->get();
        $services = Service::where('branch_id', $branch->id)
                        ->where('is_active', true)
                        ->with('serviceCategory')
                        ->orderBy('name_en')
                        ->get()
                        ->groupBy('service_category_id');

        $catalog       = app(PermissionCatalog::class);
        $branches      = $this->company()->branches()->orderBy('sort_order')->get();
        $roleSummaries = $catalog->roleSummaries();

        return view('company.employees.create', compact(
            'branch', 'roles', 'services', 'branches', 'catalog', 'roleSummaries'
        ));
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        if (! $branch->company->canAddEmployee()) {
            return redirect()
                ->route('company.branches.employees.index', $branch)
                ->with('error', __('You have reached the maximum number of employees allowed by your plan.'));
        }

        $data = $request->validate([
            'name_en'              => ['required', 'string', 'max:255'],
            'name_ar'              => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'unique:employees,email'],
            'dial_code'            => ['required', 'string', 'max:5'],
            'phone_number'         => ['required', 'string', 'max:15'],
            'role_id'              => ['required', 'exists:roles,id'],
            // Branch access (WHERE) — independent of Full Access (WHAT).
            'access_mode'          => ['nullable', 'in:selected,all'],
            'branch_ids'           => ['nullable', 'array'],
            'branch_ids.*'         => ['integer'],
            'full_access'          => ['nullable', 'boolean'],
            // Advanced permission overrides (optional; module-level).
            'overrides'            => ['nullable', 'array'],
            'overrides.*'          => ['nullable', 'in:default,none,view,manage'],
            'per_branch'           => ['nullable', 'boolean'],
            'branch_overrides'         => ['nullable', 'array'],
            'branch_overrides.*'       => ['nullable', 'array'],
            'branch_overrides.*.*'     => ['nullable', 'in:default,none,view,manage'],
            'password'             => ['required', 'string', 'min:8'],
            'bio'                  => ['nullable', 'string', 'max:1000'],
            'image'                => ['nullable', 'image', 'max:10240'],
            'is_active'    => ['nullable', 'boolean'],
            'is_bookable'  => ['nullable', 'boolean'],
            'service_ids'           => ['nullable', 'array'],
            'service_ids.*'         => ['exists:services,id'],
            'service_price'         => ['nullable', 'array'],
            'service_price.*'       => ['nullable', 'numeric', 'min:0'],
            'service_duration'      => ['nullable', 'array'],
            'service_duration.*'    => ['nullable', 'integer', 'min:1', 'max:1440'],
            // Compensation
            'comp_type'             => ['nullable', 'in:salary,commission,mixed'],
            'comp_currency'         => ['nullable', 'string', 'size:3'],
            'comp_base_amount'      => ['nullable', 'numeric', 'min:0'],
            'comp_pay_period'       => ['nullable', 'in:daily,weekly,monthly'],
            'comp_commission_type'  => ['nullable', 'in:flat,per_service'],
            'comp_commission_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comp_service_rates'    => ['nullable', 'array'],
            'comp_service_rates.*'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comp_product_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comp_overtime_hourly_rate'    => ['nullable', 'numeric', 'min:0'],
            'working_hours'              => ['nullable', 'array'],
            'working_hours.*.is_working' => ['nullable', 'boolean'],
            'working_hours.*.start_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.end_time'   => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.shifts'                => ['nullable', 'array', 'max:4'],
            'working_hours.*.shifts.*.start_time'   => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.shifts.*.end_time'     => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'social_links'               => ['nullable', 'array'],
            'social_links.*'             => ['nullable', 'string', 'max:500'],
            // HR fields
            'contract_type'              => ['nullable', 'in:' . implode(',', array_keys(Employee::CONTRACT_TYPES))],
            'hire_date'                  => ['nullable', 'date'],
            'contract_end_date'          => ['nullable', 'date', 'after_or_equal:hire_date'],
            'annual_leave_days'         => ['nullable', 'integer', 'min:0', 'max:365'],
            'national_id'               => ['nullable', 'string', 'max:30'],
            'iban'                      => ['nullable', 'string', 'max:40'],
            'bank_name'                 => ['nullable', 'string', 'max:255'],
            'emergency_contact_name'    => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone'   => ['nullable', 'string', 'max:30'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'qualifications'            => ['nullable', 'string', 'max:2000'],
            'license_number'            => ['nullable', 'string', 'max:50'],
            'license_expiry'            => ['nullable', 'date'],
        ]);

        $this->validateShiftTimes($request);

        // Branch access: selected branches, or all. Selected requires at least one.
        $accessMode = $data['access_mode'] ?? 'selected';
        if ($accessMode !== 'all' && empty($data['branch_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_ids' => __('Select at least one branch, or choose All branches.'),
            ]);
        }

        $imagePath = $request->hasFile('image')
            ? $this->storeAsWebP($request->file('image'))
            : null;

        $employee = Employee::create([
            'branch_id'   => $branch->id, // home branch; adjusted by the access writer
            'company_id'  => $this->company()->id,
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'email'       => $data['email'] ?? null,
            'phone'       => $this->buildPhone($request),
            'role_id'     => $data['role_id'],
            'password'    => Hash::make($data['password']),
            'bio'         => $data['bio'] ?? null,
            'image'       => $imagePath,
            'is_active'   => $request->boolean('is_active'),
            'is_bookable' => $request->boolean('is_bookable'),
            'contract_type'              => $data['contract_type'] ?? null,
            'hire_date'                  => $data['hire_date'] ?? null,
            'contract_end_date'          => $data['contract_end_date'] ?? null,
            'annual_leave_days'         => $data['annual_leave_days'] ?? 21,
            'national_id'               => $data['national_id'] ?? null,
            'iban'                      => $data['iban'] ?? null,
            'bank_name'                 => $data['bank_name'] ?? null,
            'emergency_contact_name'    => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone'   => $data['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $data['emergency_contact_relation'] ?? null,
            'qualifications'            => $data['qualifications'] ?? null,
            'license_number'            => $data['license_number'] ?? null,
            'license_expiry'            => $data['license_expiry'] ?? null,
        ]);

        // Branch access (WHERE) + Full Access flag + optional overrides (L2/L3/L4).
        app(EmployeeAccessWriter::class)->apply(
            $employee,
            $this->accessInput($request),
            $this->company()->branches()->pluck('id')
        );

        // Sync services with optional per-employee price & duration overrides
        $employee->services()->sync($this->buildServiceSyncData($request));

        // Save compensation
        $this->syncCompensation($employee, $request);

        // Save working hours
        $this->syncWorkingHours($employee, $request->input('working_hours', []));

        // Save social links
        SocialLink::syncFor($employee, $request->input('social_links', []));

        return redirect()
            ->route('company.branches.employees.index', $branch)
            ->with('success', __('Employee created successfully.'));
    }

    public function edit(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        $branches = $this->company()->branches()->orderBy('sort_order')->get();
        $branch   = $employee->branch ?? $branches->first();
        $roles    = Role::query()->orderBy('label_en')->get();

        // Null branch_id = works across all branches → offer every branch's services
        $serviceBranchIds = $employee->branch_id ? [$employee->branch_id] : $branches->pluck('id')->all();
        $services = Service::whereIn('branch_id', $serviceBranchIds)
                        ->where('is_active', true)
                        ->with('serviceCategory')
                        ->orderBy('name_en')
                        ->get()
                        ->groupBy('service_category_id');

        $employeeServices    = $employee->services()->get();
        $selectedServiceIds  = $employeeServices->pluck('id')->toArray();
        $servicePivot        = $employeeServices->keyBy('id')->map(fn($s) => [
            'price'            => $s->pivot->price,
            'duration_minutes' => $s->pivot->duration_minutes,
        ]);
        $compensation        = $employee->compensation;
        $serviceCommissions  = $employee->serviceCommissions()->pluck('rate', 'service_id')->toArray();
        $workingHours        = $employee->workingHours()->orderBy('shift_number')->get()->groupBy('day_of_week');
        $socialLinks         = $employee->socialLinks()->get()->keyBy('platform');

        // ── Branch access + permission prefill ──
        $catalog       = app(PermissionCatalog::class);
        $roleSummaries = $catalog->roleSummaries();

        $accessMode        = $employee->all_branches ? 'all' : 'selected';
        $selectedBranchIds = $employee->branches()->pluck('branches.id')->all();

        $l3Effects = $employee->permissionOverrides()->get()
            ->mapWithKeys(fn ($p) => [(int) $p->id => $p->pivot->effect])->all();
        $l3Levels  = $catalog->levelsFromEffects($l3Effects);

        $l4Levels  = $employee->branchPermissionOverrides()->get()
            ->groupBy('branch_id')
            ->map(fn ($rows) => $catalog->levelsFromEffects(
                $rows->mapWithKeys(fn ($r) => [(int) $r->permission_id => $r->effect])->all()
            ))->all();

        return view('company.employees.edit', [
            'employee'           => $employee,
            'branch'             => $branch,
            'branches'           => $branches,
            'roles'              => $roles,
            'services'           => $services,
            'selectedServiceIds' => $selectedServiceIds,
            'servicePivot'       => $servicePivot,
            'compensation'       => $compensation,
            'serviceCommissions' => $serviceCommissions,
            'workingHours'       => $workingHours,
            'socialLinks'        => $socialLinks,
            'catalog'            => $catalog,
            'roleSummaries'      => $roleSummaries,
            'accessMode'         => $accessMode,
            'selectedBranchIds'  => $selectedBranchIds,
            'l3Levels'           => $l3Levels,
            'l4Levels'           => $l4Levels,
            'perBranch'          => ! empty($l4Levels),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'name_en'               => ['required', 'string', 'max:255'],
            'name_ar'               => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', "unique:employees,email,{$employee->id}"],
            'dial_code'             => ['required', 'string', 'max:5'],
            'phone_number'          => ['required', 'string', 'max:15'],
            'role_id'               => ['required', 'exists:roles,id'],
            'access_mode'           => ['nullable', 'in:selected,all'],
            'branch_ids'            => ['nullable', 'array'],
            'branch_ids.*'          => ['integer'],
            'full_access'           => ['nullable', 'boolean'],
            'overrides'             => ['nullable', 'array'],
            'overrides.*'           => ['nullable', 'in:default,none,view,manage'],
            'per_branch'            => ['nullable', 'boolean'],
            'branch_overrides'      => ['nullable', 'array'],
            'branch_overrides.*'    => ['nullable', 'array'],
            'branch_overrides.*.*'  => ['nullable', 'in:default,none,view,manage'],
            'password'              => ['nullable', 'string', 'min:8'],
            'bio'                   => ['nullable', 'string', 'max:1000'],
            'image'                 => ['nullable', 'image', 'max:10240'],
            'is_active'             => ['nullable', 'boolean'],
            'is_bookable'           => ['nullable', 'boolean'],
            'service_ids'           => ['nullable', 'array'],
            'service_ids.*'         => ['exists:services,id'],
            'service_price'         => ['nullable', 'array'],
            'service_price.*'       => ['nullable', 'numeric', 'min:0'],
            'service_duration'      => ['nullable', 'array'],
            'service_duration.*'    => ['nullable', 'integer', 'min:1', 'max:1440'],
            'working_hours'              => ['nullable', 'array'],
            'working_hours.*.is_working' => ['nullable', 'boolean'],
            'working_hours.*.start_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.end_time'   => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.shifts'                => ['nullable', 'array', 'max:4'],
            'working_hours.*.shifts.*.start_time'   => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.shifts.*.end_time'     => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'social_links'               => ['nullable', 'array'],
            'social_links.*'             => ['nullable', 'string', 'max:500'],
            // Compensation
            'comp_type'                  => ['nullable', 'in:salary,commission,mixed'],
            'comp_currency'              => ['nullable', 'string', 'size:3'],
            'comp_base_amount'           => ['nullable', 'numeric', 'min:0'],
            'comp_pay_period'            => ['nullable', 'in:daily,weekly,monthly'],
            'comp_commission_type'       => ['nullable', 'in:flat,per_service'],
            'comp_commission_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comp_service_rates'         => ['nullable', 'array'],
            'comp_service_rates.*'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comp_product_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'comp_overtime_hourly_rate'    => ['nullable', 'numeric', 'min:0'],
            // HR fields
            'contract_type'              => ['nullable', 'in:' . implode(',', array_keys(Employee::CONTRACT_TYPES))],
            'hire_date'                  => ['nullable', 'date'],
            'contract_end_date'          => ['nullable', 'date', 'after_or_equal:hire_date'],
            'annual_leave_days'         => ['nullable', 'integer', 'min:0', 'max:365'],
            'national_id'               => ['nullable', 'string', 'max:30'],
            'iban'                      => ['nullable', 'string', 'max:40'],
            'bank_name'                 => ['nullable', 'string', 'max:255'],
            'emergency_contact_name'    => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone'   => ['nullable', 'string', 'max:30'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'qualifications'            => ['nullable', 'string', 'max:2000'],
            'license_number'            => ['nullable', 'string', 'max:50'],
            'license_expiry'            => ['nullable', 'date'],
        ]);

        $this->validateShiftTimes($request);

        // Branch access: selected branches, or all. Selected requires at least one.
        $company    = $this->company();
        $accessMode = $data['access_mode'] ?? 'selected';
        if ($accessMode !== 'all' && empty($data['branch_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_ids' => __('Select at least one branch, or choose All branches.'),
            ]);
        }

        $oldAccess = $employee->all_branches
            ? __('All branches')
            : $employee->branches()->pluck('branches.id')->sort()->implode(',');

        $updateData = [
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'email'       => $data['email'] ?? null,
            'phone'       => $this->buildPhone($request),
            'role_id'     => $data['role_id'],
            'bio'         => $data['bio'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'is_bookable' => $request->boolean('is_bookable'),
            'contract_type'              => $data['contract_type'] ?? $employee->contract_type,
            'hire_date'                  => $data['hire_date'] ?? $employee->hire_date,
            'contract_end_date'          => $data['contract_end_date'] ?? $employee->contract_end_date,
            'annual_leave_days'         => $data['annual_leave_days'] ?? $employee->annual_leave_days,
            'national_id'               => $data['national_id'] ?? $employee->national_id,
            'iban'                      => $data['iban'] ?? $employee->iban,
            'bank_name'                 => $data['bank_name'] ?? $employee->bank_name,
            'emergency_contact_name'    => $data['emergency_contact_name'] ?? $employee->emergency_contact_name,
            'emergency_contact_phone'   => $data['emergency_contact_phone'] ?? $employee->emergency_contact_phone,
            'emergency_contact_relation' => $data['emergency_contact_relation'] ?? $employee->emergency_contact_relation,
            'qualifications'            => $data['qualifications'] ?? $employee->qualifications,
            'license_number'            => $data['license_number'] ?? $employee->license_number,
            'license_expiry'            => $data['license_expiry'] ?? $employee->license_expiry,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('image')) {
            if ($employee->image) {
                Storage::disk('public')->delete($employee->image);
            }
            $updateData['image'] = $this->storeAsWebP($request->file('image'));
        }

        $employee->update($updateData);

        // Branch access (WHERE) + Full Access flag + optional overrides (L2/L3/L4).
        app(EmployeeAccessWriter::class)->apply(
            $employee,
            $this->accessInput($request),
            $company->branches()->pluck('id')
        );
        $employee->refresh();

        // Sync services with optional per-employee price & duration overrides.
        // Only services of the employee's accessible branches are kept.
        $accessServiceBranchIds = $employee->all_branches
            ? $company->branches()->pluck('id')
            : $employee->branches()->pluck('branches.id');
        $allowedServiceIds = Service::whereIn('branch_id', $accessServiceBranchIds)->pluck('id')->flip();
        $serviceSync = array_filter(
            $this->buildServiceSyncData($request),
            fn ($serviceId) => $allowedServiceIds->has((int) $serviceId),
            ARRAY_FILTER_USE_KEY
        );
        $employee->services()->sync($serviceSync);

        // Sync compensation
        $this->syncCompensation($employee, $request);

        // Sync working hours
        $this->syncWorkingHours($employee, $request->input('working_hours', []));

        // Sync social links
        SocialLink::syncFor($employee, $request->input('social_links', []));

        $newAccess = $employee->all_branches
            ? __('All branches')
            : $employee->branches()->pluck('branches.id')->sort()->implode(',');
        if ($newAccess !== $oldAccess) {
            \App\Support\Auditor::log(
                "Updated {$employee->localizedName()} branch access ({$oldAccess} → {$newAccess})",
                $employee
            );
        }

        return redirect()
            ->route('company.branches.employees.index', $employee->branch ?? $company->branches()->orderBy('sort_order')->first())
            ->with('success', __('Employee updated successfully.'));
    }

    private function syncCompensation(Employee $employee, Request $request): void
    {
        $type = $request->input('comp_type');
        if (! $type) return;

        $comp = $employee->compensation()->updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'type'            => $type,
                'base_amount'     => in_array($type, ['salary', 'mixed']) ? $request->input('comp_base_amount') : null,
                'currency'        => $request->input('comp_currency', config('booksy.default_currency', 'SYP')),
                'pay_period'      => $request->input('comp_pay_period', 'monthly'),
                'commission_type' => in_array($type, ['commission', 'mixed']) ? $request->input('comp_commission_type') : null,
                'commission_rate' => ($request->input('comp_commission_type') === 'flat') ? $request->input('comp_commission_rate') : null,
                'product_commission_rate' => (float) $request->input('comp_product_commission_rate', 0),
                'overtime_hourly_rate'    => (float) $request->input('comp_overtime_hourly_rate', 0) ?: null,
            ]
        );

        // Per-service rates
        if (in_array($type, ['commission', 'mixed']) && $request->input('comp_commission_type') === 'per_service') {
            $rates = $request->input('comp_service_rates', []);
            $sync  = [];
            foreach ($rates as $serviceId => $rate) {
                if ($rate !== null && $rate !== '') {
                    $sync[$serviceId] = ['rate' => (float) $rate];
                }
            }
            // Use direct DB sync on employee_service_commissions
            $employee->serviceCommissions()->delete();
            foreach ($sync as $serviceId => $pivot) {
                $employee->serviceCommissions()->create([
                    'service_id' => $serviceId,
                    'rate'       => $pivot['rate'],
                ]);
            }
        } else {
            // Clear per-service rates if not using per_service mode
            $employee->serviceCommissions()->delete();
        }
    }

    private function syncWorkingHours(Employee $employee, array $hours): void
    {
        foreach (range(0, 6) as $day) {
            $row       = $hours[$day] ?? [];
            $isWorking = ! empty($row['is_working']);

            // Collect shifts: new multi-shift format, falling back to the legacy single start/end pair
            $shifts = collect($row['shifts'] ?? [])
                ->filter(fn ($s) => ! empty($s['start_time']) && ! empty($s['end_time']))
                ->values();

            if ($shifts->isEmpty() && ! empty($row['start_time']) && ! empty($row['end_time'])) {
                $shifts = collect([['start_time' => $row['start_time'], 'end_time' => $row['end_time']]]);
            }

            $employee->workingHours()->where('day_of_week', $day)->delete();

            if (! $isWorking || $shifts->isEmpty()) {
                $employee->workingHours()->create([
                    'day_of_week'  => $day,
                    'is_working'   => false,
                    'shift_number' => 1,
                ]);
                continue;
            }

            foreach ($shifts->sortBy('start_time')->values() as $i => $shift) {
                $employee->workingHours()->create([
                    'day_of_week'  => $day,
                    'is_working'   => true,
                    'shift_number' => $i + 1,
                    'start_time'   => substr($shift['start_time'], 0, 5),
                    'end_time'     => substr($shift['end_time'], 0, 5),
                ]);
            }
        }
    }

    /**
     * Offboarding: record resignation/termination with reason & date and
     * deactivate the employee. The settlement summary (leave balance, dues)
     * is shown in the confirmation modal before this runs.
     */
    public function offboard(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'termination_type'   => ['required', 'in:' . implode(',', array_keys(Employee::TERMINATION_TYPES))],
            'termination_date'   => ['required', 'date'],
            'termination_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee->update([
            'termination_date'   => $data['termination_date'],
            'termination_type'   => $data['termination_type'],
            'termination_reason' => $data['termination_reason'] ?? null,
            'is_active'          => false,
            'is_bookable'        => false,
        ]);

        $typeLabel = __(Employee::TERMINATION_TYPES[$data['termination_type']]['label_key']);
        \App\Support\Auditor::log(
            "Offboarded {$employee->localizedName()} — {$typeLabel} ({$data['termination_date']})",
            $employee
        );

        return redirect()
            ->route('company.employees.show', $employee)
            ->with('success', __('Employee offboarded — :type', ['type' => $typeLabel]));
    }

    /** Undo offboarding: clear the termination record and reactivate. */
    public function reinstate(Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $employee->update([
            'termination_date'   => null,
            'termination_type'   => null,
            'termination_reason' => null,
            'is_active'          => true,
        ]);

        \App\Support\Auditor::log("Reinstated {$employee->localizedName()}", $employee);

        return back()->with('success', __('Employee reinstated successfully.'));
    }

    /** Combine dial_code + phone_number into the stored phone format (same as CustomerController). */
    private function buildPhone(Request $request): string
    {
        $dialCode    = $request->input('dial_code', config('booksy.default_dial_code', '+963'));
        $phoneNumber = preg_replace('/[^0-9]/', '', $request->input('phone_number', ''));

        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        $dialCodes = config('booksy.dial_codes', []);
        if (isset($dialCodes[$dialCode])) {
            $min = $dialCodes[$dialCode]['digits_min'];
            $max = $dialCodes[$dialCode]['digits_max'];
            $len = strlen($phoneNumber);
            if ($len < $min || $len > $max) {
                $country = $dialCodes[$dialCode]['name_ar'] ?? $dialCodes[$dialCode]['name_en'];
                $msg = ($min === $max)
                    ? __('Phone number for :country must be exactly :n digits.', ['country' => $country, 'n' => $min])
                    : __('Phone number for :country must be between :min and :max digits.', ['country' => $country, 'min' => $min, 'max' => $max]);
                throw \Illuminate\Validation\ValidationException::withMessages(['phone_number' => $msg]);
            }
        }

        return ltrim($dialCode, '+') . $phoneNumber;
    }

    private function buildServiceSyncData(Request $request): array
    {
        $ids       = $request->input('service_ids', []);
        $prices    = $request->input('service_price', []);
        $durations = $request->input('service_duration', []);
        $sync      = [];

        foreach ($ids as $id) {
            $sync[$id] = [
                'price'            => isset($prices[$id]) && $prices[$id] !== '' ? $prices[$id] : null,
                'duration_minutes' => isset($durations[$id]) && $durations[$id] !== '' ? (int)$durations[$id] : null,
            ];
        }

        return $sync;
    }

    private function storeAsWebP(UploadedFile $file, int $quality = 82): string
    {
        $dir      = 'employees/images';
        $filename = $dir . '/' . uniqid('emp_', true) . '.webp';
        $fullPath = storage_path('app/public/' . $filename);

        Storage::disk('public')->makeDirectory($dir);

        $mime = $file->getMimeType();
        $src  = match (true) {
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($file->getRealPath()),
            str_contains($mime, 'png')  => imagecreatefrompng($file->getRealPath()),
            str_contains($mime, 'gif')  => imagecreatefromgif($file->getRealPath()),
            str_contains($mime, 'webp') => imagecreatefromwebp($file->getRealPath()),
            default                     => @imagecreatefromstring(file_get_contents($file->getRealPath())),
        };

        if (! $src) {
            return $file->store($dir, 'public');
        }

        // Preserve PNG/GIF transparency on a white background
        if (str_contains($mime, 'png') || str_contains($mime, 'gif')) {
            $w    = imagesx($src);
            $h    = imagesy($src);
            $dest = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($dest, 255, 255, 255);
            imagefilledrectangle($dest, 0, 0, $w, $h, $white);
            imagecopy($dest, $src, 0, 0, 0, 0, $w, $h);
            imagedestroy($src);
            $src = $dest;
        }

        imagewebp($src, $fullPath, $quality);
        imagedestroy($src);

        return $filename;
    }

    public function show(Employee $employee): View
    {
        $this->authoriseEmployee($employee);

        $employee->load([
            'role', 'branch', 'compensation', 'workingHours',
            'socialLinks', 'serviceCommissions',
        ]);

        $services = $employee->services()->with('serviceCategory')->get();

        $now = now();
        $appointmentsThisMonth = $employee->appointments()
            ->whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)
            ->count();

        $revenueThisMonth = $employee->appointments()
            ->whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)
            ->where('status', 'completed')
            ->sum('total_price');

        $completedThisMonth = $employee->appointments()
            ->whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)
            ->where('status', 'completed')
            ->count();

        $totalAppointments = $employee->appointments()->count();
        $totalRevenue = $employee->appointments()->where('status', 'completed')->sum('total_price');

        $appointments = $employee->appointments()
            ->with(['service', 'customer', 'branch'])
            ->orderByDesc('start_time')
            ->paginate(10);

        $attendanceThisMonth = $employee->attendanceRecords()
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->get();

        $attendanceStats = [
            'present' => $attendanceThisMonth->whereIn('status', ['on_time', 'late'])->count(),
            'late'    => $attendanceThisMonth->where('status', 'late')->count(),
            'absent'  => $attendanceThisMonth->where('status', 'absent')->count(),
        ];

        $leaves = $employee->leaves()->orderByDesc('start_date')->limit(5)->get();
        $deductions = $employee->deductions()->orderByDesc('created_at')->limit(5)->get();
        $attendanceHistory = $employee->attendanceRecords()->orderByDesc('date')->limit(30)->get();

        $employee->load('leaves');
        $annualUsed      = $employee->annualLeaveUsed();
        $annualRemaining = $employee->annualLeaveRemaining();

        return view('company.employees.show', compact(
            'employee', 'services',
            'appointmentsThisMonth', 'revenueThisMonth', 'completedThisMonth',
            'totalAppointments', 'totalRevenue', 'appointments',
            'attendanceStats', 'leaves', 'deductions', 'attendanceHistory',
            'annualUsed', 'annualRemaining'
        ));
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
