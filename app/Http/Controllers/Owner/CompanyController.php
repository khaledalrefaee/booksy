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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->with('category')
            ->orderBy('id')
            ->get();

        $categories = Category::query()->orderBy('sort_order')->get();

        return view('owner.companies.index', compact('companies', 'categories'));
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
