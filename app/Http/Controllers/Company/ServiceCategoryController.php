<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    public function index(): View
    {
        $serviceCategories = $this->company()
            ->serviceCategories()
            ->withCount('services')
            ->orderBy('sort_order')
            ->get();

        return view('company.service-categories.index', compact('serviceCategories'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name_en'    => ['required', 'string', 'max:255'],
            'name_ar'    => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $company = $this->company();

        $category = $company->serviceCategories()->create([
            'name_en'    => $data['name_en'],
            'name_ar'    => $data['name_ar'] ?? null,
            'sort_order' => $data['sort_order'] ?? (int) $company->serviceCategories()->max('sort_order') + 1,
            'slug'       => Str::slug($data['name_en'] . '-' . $company->id),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'category' => [
                'id' => $category->id, 'name' => $category->localizedName(), 'sort_order' => $category->sort_order,
            ]]);
        }

        return back()->with('success', __('Service category created.'));
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse|JsonResponse
    {
        abort_unless($serviceCategory->company_id === $this->company()->id, 403);

        $data = $request->validate([
            'name_en'    => ['required', 'string', 'max:255'],
            'name_ar'    => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $serviceCategory->update([
            'name_en'    => $data['name_en'],
            'name_ar'    => $data['name_ar'] ?? null,
            'sort_order' => $data['sort_order'] ?? $serviceCategory->sort_order,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'category' => [
                'id' => $serviceCategory->id, 'name' => $serviceCategory->localizedName(), 'sort_order' => $serviceCategory->sort_order,
            ]]);
        }

        return back()->with('success', __('Service category updated.'));
    }

    public function reorder(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $owned = $this->company()->serviceCategories()->pluck('id')->flip();

        foreach ($data['ids'] as $position => $id) {
            if (isset($owned[$id])) {
                ServiceCategory::where('id', $id)->update(['sort_order' => $position]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        abort_unless($serviceCategory->company_id === $this->company()->id, 403);

        if ($serviceCategory->services()->exists()) {
            return back()->withErrors(['delete' => __('Cannot delete: has services.')]);
        }

        $serviceCategory->delete();

        return back()->with('success', __('Service category deleted.'));
    }
}
