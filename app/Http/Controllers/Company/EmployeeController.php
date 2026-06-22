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

    public function index(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $employees = $branch->employees()
            ->with(['role', 'compensation', 'serviceCommissions'])
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
            ->get();

        return view('company.employees.index', compact('branch', 'employees'));
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

        return view('company.employees.create', compact('branch', 'roles', 'services'));
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'name_en'              => ['required', 'string', 'max:255'],
            'name_ar'              => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'unique:employees,email'],
            'phone'                => ['required', 'string', 'max:30'],
            'role_id'              => ['required', 'exists:roles,id'],
            'password'             => ['required', 'string', 'min:8'],
            'bio'                  => ['nullable', 'string', 'max:1000'],
            'image'                => ['nullable', 'image', 'max:2048'],
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
            'working_hours'              => ['nullable', 'array'],
            'working_hours.*.is_working' => ['nullable', 'boolean'],
            'working_hours.*.start_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.end_time'   => ['nullable', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'social_links'               => ['nullable', 'array'],
            'social_links.*'             => ['nullable', 'string', 'max:500'],
        ]);

        $imagePath = $request->hasFile('image')
            ? $this->storeAsWebP($request->file('image'))
            : null;

        $employee = $branch->employees()->create([
            'company_id'  => $this->company()->id,
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'email'       => $data['email'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'role_id'     => $data['role_id'],
            'password'    => Hash::make($data['password']),
            'bio'         => $data['bio'] ?? null,
            'image'       => $imagePath,
            'is_active'   => $request->boolean('is_active'),
            'is_bookable' => $request->boolean('is_bookable'),
        ]);

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

        $branch   = $employee->branch;
        $roles    = Role::query()->orderBy('label_en')->get();
        $services = Service::where('branch_id', $branch->id)
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
        $workingHours        = $employee->workingHours()->get()->keyBy('day_of_week');
        $socialLinks         = $employee->socialLinks()->get()->keyBy('platform');

        return view('company.employees.edit', [
            'employee'           => $employee,
            'branch'             => $branch,
            'roles'              => $roles,
            'services'           => $services,
            'selectedServiceIds' => $selectedServiceIds,
            'servicePivot'       => $servicePivot,
            'compensation'       => $compensation,
            'serviceCommissions' => $serviceCommissions,
            'workingHours'       => $workingHours,
            'socialLinks'        => $socialLinks,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authoriseEmployee($employee);

        $data = $request->validate([
            'name_en'               => ['required', 'string', 'max:255'],
            'name_ar'               => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', "unique:employees,email,{$employee->id}"],
            'phone'                 => ['required', 'string', 'max:30'],
            'role_id'               => ['required', 'exists:roles,id'],
            'password'              => ['nullable', 'string', 'min:8'],
            'bio'                   => ['nullable', 'string', 'max:1000'],
            'image'                 => ['nullable', 'image', 'max:2048'],
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
        ]);

        $updateData = [
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'email'       => $data['email'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'role_id'     => $data['role_id'],
            'bio'         => $data['bio'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'is_bookable' => $request->boolean('is_bookable'),
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

        // Sync services with optional per-employee price & duration overrides
        $employee->services()->sync($this->buildServiceSyncData($request));

        // Sync compensation
        $this->syncCompensation($employee, $request);

        // Sync working hours
        $this->syncWorkingHours($employee, $request->input('working_hours', []));

        // Sync social links
        SocialLink::syncFor($employee, $request->input('social_links', []));

        return redirect()
            ->route('company.branches.employees.index', $employee->branch)
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
            $row        = $hours[$day] ?? [];
            $isWorking  = ! empty($row['is_working']);
            $start      = $isWorking && isset($row['start_time']) ? substr($row['start_time'], 0, 5) : null;
            $end        = $isWorking && isset($row['end_time'])   ? substr($row['end_time'],   0, 5) : null;
            $employee->workingHours()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'is_working' => $isWorking,
                    'start_time' => $start,
                    'end_time'   => $end,
                ]
            );
        }
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
