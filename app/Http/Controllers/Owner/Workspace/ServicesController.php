<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Company;
use App\Models\Service;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Company Workspace — Services & Pricing tab.
 * Lists a company's services across branches with safe inline active/price
 * edits. Deep DnD/packages/bulk editing defers to the full editor.
 */
class ServicesController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        $branchIds = $company->branches()->pluck('id');
        $q = $this->searchTerm(request());

        $services = Service::query()
            ->whereIn('branch_id', $branchIds)
            ->with(['branch', 'serviceCategory'])
            ->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub->where('name_en', 'like', "%{$q}%")->orWhere('name_ar', 'like', "%{$q}%")))
            ->orderByLocalizedName()
            ->get();

        return $this->tab('services', $company, compact('services', 'q'));
    }

    public function toggleActive(Company $company, Service $service)
    {
        $this->guard($company, $service);
        abort_unless(Gate::allows('owner-can', 'catalog.manage') || Gate::allows('owner-can', 'company-workspace.view'), 403);

        $service->update(['is_active' => ! $service->is_active]);
        OwnerAudit::record('company.service.toggle-active', $service, new: ['is_active' => $service->is_active]);

        return $this->actionOk($service->is_active ? __('Service activated.') : __('Service deactivated.'));
    }

    public function updatePrice(Company $company, Service $service, Request $request)
    {
        $this->guard($company, $service);
        abort_unless(Gate::allows('owner-can', 'catalog.manage') || Gate::allows('owner-can', 'company-workspace.view'), 403);

        $validated = $request->validate(['price' => ['required', 'numeric', 'min:0']]);
        $old = $service->price;
        $service->update(['price' => $validated['price']]);
        OwnerAudit::record('company.service.price-update', $service, old: ['price' => $old], new: ['price' => $service->price]);

        return $this->actionOk(__('Price updated.'));
    }

    private function guard(Company $company, Service $service): void
    {
        abort_unless($company->branches()->whereKey($service->branch_id)->exists(), 404);
    }
}
