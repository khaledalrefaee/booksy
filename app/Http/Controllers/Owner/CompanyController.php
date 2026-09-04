<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCompanyRequest;
use App\Http\Requests\Owner\UpdateCompanyRequest;
use App\Http\Requests\Owner\UpdateCompanyStatusRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\Plan;
use App\Services\Owner\OwnerAudit;
use App\Support\CategoryUploadedImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $q          = trim($request->input('q', ''));
        $sortField  = in_array($request->input('sort'), ['name', 'created_at', 'status']) ? $request->input('sort') : 'created_at';
        $sortDir    = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $filterStatus     = $request->input('status', '');
        $filterCategoryId = $request->input('category_id', '');
        $filterPlanId     = $request->input('plan_id', '');
        $filterDate       = $request->input('date', '');
        $dateFrom         = $request->input('date_from', '');
        $dateTo           = $request->input('date_to', '');

        $companies  = $this->filteredQuery($request)->with(['category', 'plan'])->paginate(15)->withQueryString();
        $categories = Category::query()->orderBy('sort_order')->get();
        $plans      = Plan::query()->orderBy('sort_order')->get(['id', 'name_en', 'name_ar']);

        // Global overview counts — one grouped query for the status tallies, plus
        // "new this month". These describe the whole platform, not the filtered view.
        $statusCounts = Company::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total'     => (int) $statusCounts->sum(),
            'active'    => (int) ($statusCounts['active']    ?? 0),
            'pending'   => (int) ($statusCounts['pending']   ?? 0),
            'suspended' => (int) ($statusCounts['suspended'] ?? 0),
            'new_month' => (int) Company::query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        // How many filters are actually applied (drives the "Filters · N" badge).
        $activeFilters = collect([$filterStatus, $filterCategoryId, $filterPlanId, $filterDate])
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->count();

        return view('owner.companies.index', compact(
            'companies', 'categories', 'plans', 'stats', 'activeFilters',
            'q', 'sortField', 'sortDir',
            'filterStatus', 'filterCategoryId', 'filterPlanId', 'filterDate', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * The single source of truth for the companies listing query — shared by the
     * on-screen table and the Excel export so both honour the exact same filters
     * (search, status, category, plan, date range, sort).
     */
    private function filteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        // NOTE: Laravel's ConvertEmptyStringsToNull middleware turns empty query
        // params (e.g. ?status=) into NULL — so guard every filter with filled(),
        // never a bare `!== ''` check (null would slip through and filter to 0).
        $q          = trim((string) $request->input('q', ''));
        $sortField  = in_array($request->input('sort'), ['name', 'created_at', 'status']) ? $request->input('sort') : 'created_at';
        $sortDir    = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $status     = $request->input('status');
        $categoryId = $request->input('category_id');
        $planId     = $request->input('plan_id');
        $date       = $request->input('date');

        $query = Company::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('owner_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if (filled($status)) {
            $query->where('status', $status);
        }

        if (filled($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        if (filled($planId)) {
            $planId === 'none'
                ? $query->whereNull('plan_id')
                : $query->where('plan_id', (int) $planId);
        }

        // created_at presets + custom range
        $tz = config('app.timezone');
        match ($date) {
            'today' => $query->where('created_at', '>=', now($tz)->startOfDay()),
            'week'  => $query->where('created_at', '>=', now($tz)->startOfWeek()),
            'month' => $query->where('created_at', '>=', now($tz)->startOfMonth()),
            'custom' => (function () use ($query, $request) {
                if ($from = $request->input('date_from')) {
                    $query->whereDate('created_at', '>=', $from);
                }
                if ($to = $request->input('date_to')) {
                    $query->whereDate('created_at', '<=', $to);
                }
            })(),
            default => null,
        };

        if ($sortField === 'name') {
            $query->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar) {$sortDir}");
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        return $query;
    }

    /**
     * Export the currently-filtered companies to .xlsx. Same auth as the listing
     * (owner.auth group) and the same query, so it never leaks rows the filters
     * hide. Filename carries the date, e.g. glowrez-companies-2026-08-30.xlsx.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companies = $this->filteredQuery($request)->with(['category', 'plan'])->get();

        $filename = 'glowrez-companies-'.now()->format('Y-m-d').'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CompaniesExport($companies),
            $filename
        );
    }

    /** The blank .xlsx template the owner fills in before importing. */
    public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CompaniesImportTemplate(),
            'glowrez-companies-template.xlsx'
        );
    }

    /**
     * Bulk-create companies from an uploaded spreadsheet. Each row is validated
     * independently; bad rows are skipped and reported so a single mistake never
     * loses the whole file. Same permission as creating a company (companies.manage).
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [], ['file' => __('file')]);

        $import = new \App\Imports\CompaniesImport();
        $import->import($request->file('file'));

        OwnerAudit::record('company.import', new Company(), new: ['created' => $import->created]);

        if ($import->created > 0) {
            session()->flash('success', __(':count companies imported successfully.', ['count' => $import->created]));
        }

        // Surface up to a handful of row errors; summarise the rest.
        if (! empty($import->errors)) {
            $shown = array_slice($import->errors, 0, 5);
            $extra = count($import->errors) - count($shown);
            $msg = implode(' • ', $shown);
            if ($extra > 0) {
                $msg .= ' • '.__('(+:n more rows skipped)', ['n' => $extra]);
            }
            session()->flash('error', $msg);
        }

        if ($import->created === 0 && empty($import->errors)) {
            session()->flash('warning', __('No rows found to import.'));
        }

        return redirect()->route('owner.companies.index');
    }

    public function show(Company $company): View
    {
        // Lightweight shell only — each workspace tab lazy-loads its own scoped
        // data via its Owner\Workspace controller (see config/owner-workspace.php).
        $company->load(['category', 'plan']);

        $stats = [
            'branches'     => $company->branches()->count(),
            'employees'    => $company->employees()->count(),
            'appointments' => $company->appointments()->count(),
            'waitlist'     => $company->waitlistEntries()->count(),
        ];

        $tabs = config('owner-workspace.tabs', []);

        return view('owner.companies.show', compact('company', 'stats', 'tabs'));
    }

    public function updateSubscription(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id'         => ['nullable', 'integer', 'exists:plans,id'],
            'plan_expires_at' => ['nullable', 'date'],
            'overrides'       => ['nullable', 'array'],
            'overrides.*'     => ['nullable', 'in:,1,0'],
        ]);

        // Keep only explicit on/off overrides; empty string means "follow the plan"
        $overrides = collect($validated['overrides'] ?? [])
            ->filter(fn ($v, $k) => in_array($k, Plan::featureKeys(), true) && ($v === '1' || $v === '0'))
            ->map(fn ($v) => $v === '1')
            ->all();

        $company->fill([
            'plan_id'           => $validated['plan_id'] ?? null,
            'plan_expires_at'   => $validated['plan_expires_at'] ?? null,
            'feature_overrides' => $overrides ?: null,
        ]);

        OwnerAudit::recordChanges('company.subscription-update', $company);
        $company->save();

        return redirect()
            ->route('owner.companies.show', $company)
            ->with('success', __('Subscription updated successfully.'));
    }

    public function updateStatus(UpdateCompanyStatusRequest $request, Company $company): RedirectResponse
    {
        $newStatus = $request->validated('status');
        $reason    = $request->validated('reason');

        $company->fill(['status' => $newStatus]);

        // Track the suspension metadata so the login screen and the notice can
        // explain the block; clear it again the moment the account is restored.
        if ($newStatus === 'suspended') {
            $company->suspended_at = $company->suspended_at ?? now();
            $company->suspension_reason = $reason;
        } else {
            $company->suspended_at = null;
            $company->suspension_reason = null;
        }

        OwnerAudit::recordChanges('company.status-update', $company, $reason);
        $company->save();

        // Notify the owner (email + phone) whenever the admin sets the account to
        // suspended. The panel's own UI blocks a no-op save (from === to), so
        // reaching here with "suspended" is always a deliberate suspend action.
        if ($newStatus === 'suspended') {
            app(\App\Services\CompanyStatusNotifier::class)->sendSuspended($company, $reason);
        }

        return redirect()
            ->route('owner.companies.index')
            ->with('success', __('Company status updated.'));
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'category_id' => $validated['category_id'],
            'password' => $validated['password'],
            'status' => $validated['status'],
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = CategoryUploadedImage::storeImage(
                $request->file('logo'),
                'companies/logos'
            );
        }

        $company = Company::query()->create($data);

        OwnerAudit::record('company.create', $company, new: collect($data)->except('password')->all());

        return redirect()
            ->route('owner.companies.index')
            ->with('success', __('Company created successfully.'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $validated = $request->validated();

        $company->name_en = $validated['name_en'];
        $company->name_ar = $validated['name_ar'];
        $company->email = $validated['email'];
        $company->phone = $validated['phone'] ?? null;
        $company->category_id = $validated['category_id'];

        if (! empty($validated['password'])) {
            $company->password = $validated['password'];
        }

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $company->logo = CategoryUploadedImage::storeImage(
                $request->file('logo'),
                'companies/logos'
            );
        }

        OwnerAudit::recordChanges('company.update', $company);
        $company->save();

        return redirect()
            ->route('owner.companies.index')
            ->with('success', __('Company updated successfully.'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        OwnerAudit::record('company.delete', $company, old: ['email' => $company->email, 'status' => $company->status]);

        $company->delete();

        return redirect()
            ->route('owner.companies.index')
            ->with('success', __('Company deleted successfully.'));
    }
}
