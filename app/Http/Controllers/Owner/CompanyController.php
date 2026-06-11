<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCompanyRequest;
use App\Http\Requests\Owner\UpdateCompanyRequest;
use App\Http\Requests\Owner\UpdateCompanyStatusRequest;
use App\Models\Category;
use App\Models\Company;
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

        $query = Company::query()->with('category');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        if ($filterCategoryId !== '') {
            $query->where('category_id', (int) $filterCategoryId);
        }

        if ($sortField === 'name') {
            $query->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar) {$sortDir}");
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $companies  = $query->paginate(15)->withQueryString();
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('owner.companies.index', compact('companies', 'categories', 'q', 'sortField', 'sortDir', 'filterStatus', 'filterCategoryId'));
    }

    public function show(Company $company): View
    {
        $company->load([
            'category',
            'branches' => fn ($query) => $query->orderBy('sort_order')->with([
                'workingHours' => fn ($q) => $q->orderBy('day_of_week')->orderBy('shift_number'),
                'services' => fn ($q) => $q->orderByLocalizedName(),
            ]),
            'employees' => fn ($query) => $query->with(['branch', 'role'])->orderByLocalizedName(),
            'appointments' => fn ($query) => $query
                ->with(['branch', 'customer', 'employee', 'service'])
                ->orderByDesc('start_time')
                ->limit(50),
            'waitlistEntries' => fn ($query) => $query
                ->with(['branch', 'customer', 'service'])
                ->orderByDesc('created_at')
                ->limit(30),
        ]);

        $stats = [
            'branches' => $company->branches->count(),
            'employees' => $company->employees->count(),
            'appointments' => $company->appointments()->count(),
            'waitlist' => $company->waitlistEntries()->count(),
        ];

        return view('owner.companies.show', compact('company', 'stats'));
    }

    public function updateStatus(UpdateCompanyStatusRequest $request, Company $company): RedirectResponse
    {
        $company->update(['status' => $request->validated('status')]);

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

        Company::query()->create($data);

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

        $company->delete();

        return redirect()
            ->route('owner.companies.index')
            ->with('success', __('Company deleted successfully.'));
    }
}
