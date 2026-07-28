<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResourceController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authorise(Resource $resource): void
    {
        abort_unless($resource->branch->company_id === $this->company()->id, 403);
    }

    public function index(Request $request): View
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();

        $resources = Resource::query()
            ->whereIn('branch_id', $branches->pluck('id'))
            ->with(['branch', 'serviceCategories'])
            ->withCount('services')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->orderBy('branch_id')->orderBy('sort_order')->orderBy('name_en')
            ->get();

        $serviceCategories = $company->serviceCategories()->orderBy('sort_order')->get();

        return view('company.resources.index', compact('branches', 'resources', 'serviceCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id'     => ['required', 'exists:branches,id'],
            'name_en'       => ['required', 'string', 'max:255'],
            'name_ar'       => ['nullable', 'string', 'max:255'],
            'type'          => ['required', 'in:' . implode(',', Resource::TYPES)],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'category_ids'  => ['nullable', 'array'],
            'category_ids.*'=> ['integer'],
        ]);

        abort_unless($this->company()->branches()->where('id', $data['branch_id'])->exists(), 403);

        $resource = Resource::create([
            'branch_id'  => $data['branch_id'],
            'name_en'    => $data['name_en'],
            'name_ar'    => $data['name_ar'] ?? null,
            'type'       => $data['type'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => true,
        ]);

        $resource->serviceCategories()->sync($this->ownCategoryIds($data['category_ids'] ?? []));

        return back()->with('success', __('Resource created.'));
    }

    public function update(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorise($resource);

        $data = $request->validate([
            'name_en'       => ['required', 'string', 'max:255'],
            'name_ar'       => ['nullable', 'string', 'max:255'],
            'type'          => ['required', 'in:' . implode(',', Resource::TYPES)],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
            'category_ids'  => ['nullable', 'array'],
            'category_ids.*'=> ['integer'],
        ]);

        $resource->update([
            'name_en'    => $data['name_en'],
            'name_ar'    => $data['name_ar'] ?? null,
            'type'       => $data['type'],
            'sort_order' => $data['sort_order'] ?? $resource->sort_order,
            'is_active'  => $request->boolean('is_active'),
        ]);

        $resource->serviceCategories()->sync($this->ownCategoryIds($data['category_ids'] ?? []));

        return back()->with('success', __('Resource updated.'));
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $this->authorise($resource);

        $resource->delete();

        return back()->with('success', __('Resource deleted.'));
    }

    /** Keep only category ids that belong to this company. */
    private function ownCategoryIds(array $ids): \Illuminate\Support\Collection
    {
        return $this->company()->serviceCategories()->whereIn('id', $ids)->pluck('id');
    }
}
