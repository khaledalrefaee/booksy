<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Http\Requests\Owner\StoreServiceRequest;
use App\Http\Requests\Owner\UpdateServiceRequest;
use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ResolvesOwnerCompany;

    public function index(Request $request, Branch $branch): View
    {
        $this->authorizeBranch($branch);

        $q                    = trim($request->input('q', ''));
        $sortField            = in_array($request->input('sort'), ['name', 'price', 'duration_minutes', 'created_at']) ? $request->input('sort') : 'name';
        $sortDir              = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $filterServiceCatId   = $request->input('service_category_id', '');
        $filterIsActive       = $request->input('is_active', '');

        $query = $branch->services()->with('serviceCategory');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%");
            });
        }

        if ($filterServiceCatId !== '') {
            $query->where('service_category_id', (int) $filterServiceCatId);
        }

        if ($filterIsActive !== '') {
            $query->where('is_active', (bool) $filterIsActive);
        }

        if ($sortField === 'name') {
            $query->orderByLocalizedName();
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $services = $query->paginate(15)->withQueryString();

        return view('owner.services.index', [
            'branch'              => $branch,
            'services'            => $services,
            'q'                   => $q,
            'sortField'           => $sortField,
            'sortDir'             => $sortDir,
            'filterServiceCatId'  => $filterServiceCatId,
            'filterIsActive'      => $filterIsActive,
            'serviceCategories'   => $this->serviceCategories(),
        ]);
    }

    public function create(Branch $branch): View
    {
        $this->authorizeBranch($branch);

        return view('owner.services.create', [
            'branch' => $branch,
            'serviceCategories' => $this->serviceCategories(),
        ]);
    }

    public function store(StoreServiceRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorizeBranch($branch);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $branch->services()->create($data);

        return redirect()
            ->route('owner.branches.services.index', $branch)
            ->with('success', __('Service created successfully.'));
    }

    public function edit(Service $service): View
    {
        $this->authorizeService($service);

        return view('owner.services.edit', [
            'branch' => $service->branch,
            'service' => $service,
            'serviceCategories' => $this->serviceCategories(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorizeService($service);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $service->update($data);

        return redirect()
            ->route('owner.branches.services.index', $service->branch)
            ->with('success', __('Service updated successfully.'));
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $this->authorizeService($service);

        $service->update(['is_active' => ! $service->is_active]);

        return redirect()
            ->route('owner.branches.services.index', $service->branch)
            ->with('success', __('Service status updated.'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorizeService($service);

        $branch = $service->branch;
        $service->delete();

        return redirect()
            ->route('owner.branches.services.index', $branch)
            ->with('success', __('Service deleted successfully.'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ServiceCategory>
     */
    private function serviceCategories()
    {
        return ServiceCategory::query()->orderBy('sort_order')->orderBy('id')->get();
    }
}
