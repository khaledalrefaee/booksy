<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $q         = trim($request->input('q', ''));
        $sortField = in_array($request->input('sort'), ['name', 'sort_order', 'services_count', 'created_at']) ? $request->input('sort') : 'sort_order';
        $sortDir   = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $query = ServiceCategory::query()->withCount('services');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%");
            });
        }

        if ($sortField === 'name') {
            $query->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar) {$sortDir}");
        } else {
            $query->orderBy($sortField, $sortDir)->orderBy('id', $sortDir);
        }

        $serviceCategories = $query->paginate(15)->withQueryString();

        return view('owner.service-categories.index', compact('serviceCategories', 'q', 'sortField', 'sortDir'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $category = ServiceCategory::query()->create([
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'slug' => Str::slug($validated['name_en']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (! $request->filled('sort_order')) {
            $category->update(['sort_order' => $category->id]);
        }

        return redirect()
            ->route('owner.service-categories.index')
            ->with('success', __('Service category created successfully.'));
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $serviceCategory->name_en = $validated['name_en'];
        $serviceCategory->name_ar = $validated['name_ar'];
        $serviceCategory->slug = Str::slug($validated['name_en']);

        if (isset($validated['sort_order'])) {
            $serviceCategory->sort_order = (int) $validated['sort_order'];
        }

        $serviceCategory->save();

        return redirect()
            ->route('owner.service-categories.index')
            ->with('success', __('Service category updated successfully.'));
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        if ($serviceCategory->services()->exists()) {
            return redirect()
                ->route('owner.service-categories.index')
                ->with('warning', __('Cannot delete a category that has services. Reassign or delete services first.'));
        }

        $serviceCategory->delete();

        return redirect()
            ->route('owner.service-categories.index')
            ->with('success', __('Service category deleted successfully.'));
    }
}
