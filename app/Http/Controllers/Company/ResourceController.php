<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResourceController extends Controller
{
    private const MAX_UNITS = 20;

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
            'quantity'      => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_UNITS],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'category_ids'  => ['nullable', 'array'],
            'category_ids.*'=> ['integer'],
        ], [], ['quantity' => __('Units')]);

        abort_unless($this->company()->branches()->where('id', $data['branch_id'])->exists(), 403);

        // N identical units (3 chairs, 2 laser devices) are created as numbered
        // resources — the booking engine already treats several linked resources
        // as a pool, so no booking logic needs to know about "quantity".
        $quantity = (int) ($data['quantity'] ?? 1);
        $units = [];
        for ($i = 1; $i <= $quantity; $i++) {
            $units[] = [
                'name_en' => $quantity > 1 ? "{$data['name_en']} {$i}" : $data['name_en'],
                'name_ar' => filled($data['name_ar'] ?? null)
                    ? ($quantity > 1 ? "{$data['name_ar']} {$i}" : $data['name_ar'])
                    : null,
            ];
        }

        foreach ($units as $unit) {
            $this->assertNameFree((int) $data['branch_id'], $unit['name_en']);
        }

        $categoryIds = $this->ownCategoryIds($data['category_ids'] ?? []);
        $sort = (int) ($data['sort_order'] ?? 0);

        foreach ($units as $i => $unit) {
            $resource = Resource::create([
                'branch_id'  => $data['branch_id'],
                'name_en'    => $unit['name_en'],
                'name_ar'    => $unit['name_ar'],
                'type'       => $data['type'],
                'sort_order' => $sort + $i,
                'is_active'  => true,
            ]);
            $resource->serviceCategories()->sync($categoryIds);
        }

        return back()->with('success', $quantity > 1
            ? __(':count resources created.', ['count' => $quantity])
            : __('Resource created.'));
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

        $this->assertNameFree($resource->branch_id, $data['name_en'], $resource->id);

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

    /** A branch can't hold two resources with the same (English) name. */
    private function assertNameFree(int $branchId, string $name, ?int $ignoreId = null): void
    {
        $taken = Resource::where('branch_id', $branchId)
            ->where('name_en', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'name_en' => __('A resource named ":name" already exists in this branch.', ['name' => $name]),
            ]);
        }
    }

    /** Keep only category ids that belong to this company. */
    private function ownCategoryIds(array $ids): \Illuminate\Support\Collection
    {
        return $this->company()->serviceCategories()->whereIn('id', $ids)->pluck('id');
    }
}
