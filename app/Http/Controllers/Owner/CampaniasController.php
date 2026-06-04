<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCampaniaRequest;
use App\Http\Requests\Owner\UpdateCampaniaRequest;
use App\Http\Requests\Owner\UpdateCampaniaStatusRequest;
use App\Models\Category;
use App\Models\Company;
use App\Support\CategoryUploadedImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CampaniasController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->with('category')
            ->orderBy('id')
            ->get();

        $categories = Category::query()->orderBy('sort_order')->get();

        return view('owner.campanias.index', compact('companies', 'categories'));
    }

    public function show(Company $campania): View
    {
        $campania->load([
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
            'branches' => $campania->branches->count(),
            'employees' => $campania->employees->count(),
            'appointments' => $campania->appointments()->count(),
            'waitlist' => $campania->waitlistEntries()->count(),
        ];

        return view('owner.campanias.show', compact('campania', 'stats'));
    }

    public function updateStatus(UpdateCampaniaStatusRequest $request, Company $campania): RedirectResponse
    {
        $campania->update(['status' => $request->validated('status')]);

        return redirect()
            ->route('owner.campanias.index')
            ->with('success', __('Company status updated.'));
    }

    public function store(StoreCampaniaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ,
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
            ->route('owner.campanias.index')
            ->with('success', __('Company created successfully.'));
    }

    public function update(UpdateCampaniaRequest $request, Company $campania): RedirectResponse
    {
        $validated = $request->validated();

        $campania->name_en = $validated['name_en'];
        $campania->name_ar = $validated['name_ar'];
        $campania->email = $validated['email'];
        $campania->phone = $validated['phone'] ?? null;
        $campania->category_id = $validated['category_id'];

        if (! empty($validated['password'])) {
            $campania->password = $validated['password'];
        }

        if ($request->hasFile('logo')) {
            if ($campania->logo) {
                Storage::disk('public')->delete($campania->logo);
            }
            $campania->logo = CategoryUploadedImage::storeImage(
                $request->file('logo'),
                'companies/logos'
            );
        }

        $campania->save();

        return redirect()
            ->route('owner.campanias.index')
            ->with('success', __('Company updated successfully.'));
    }

    public function destroy(Company $campania): RedirectResponse
    {
        if ($campania->logo) {
            Storage::disk('public')->delete($campania->logo);
        }

        $campania->delete();

        return redirect()
            ->route('owner.campanias.index')
            ->with('success', __('Company deleted successfully.'));
    }
}
