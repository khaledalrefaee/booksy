<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }

    private function authoriseService(Service $service): void
    {
        abort_unless($service->branch->company_id === $this->company()->id, 403);
    }

    public function index(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $services = $branch->services()
            ->with('serviceCategory')
            ->orderBy('name_en')
            ->get();

        return view('company.services.index', compact('branch', 'services'));
    }

    public function create(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $serviceCategories = $this->company()->serviceCategories()->orderBy('sort_order')->get();

        return view('company.services.create', compact('branch', 'serviceCategories'));
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'price'               => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'duration_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branch->services()->create($data);

        return redirect()
            ->route('company.branches.services.index', $branch)
            ->with('success', __('Service created successfully.'));
    }

    public function edit(Service $service): View
    {
        $service->load('branch');
        $this->authoriseService($service);

        $serviceCategories = $this->company()->serviceCategories()->orderBy('sort_order')->get();

        return view('company.services.edit', compact('service', 'serviceCategories'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $data = $request->validate([
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'price'               => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'duration_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $service->update($data);

        return redirect()
            ->route('company.branches.services.index', $service->branch)
            ->with('success', __('Service updated successfully.'));
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $service->update(['is_active' => ! $service->is_active]);

        return redirect()
            ->route('company.branches.services.index', $service->branch)
            ->with('success', __('Service status updated.'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $branch = $service->branch;
        $service->delete();

        return redirect()
            ->route('company.branches.services.index', $branch)
            ->with('success', __('Service deleted successfully.'));
    }
}
