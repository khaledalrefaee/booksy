<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
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

        $serviceCategories = $this->company()->serviceCategories()->orderBy('sort_order')->get();

        return view('company.services.index', compact('branch', 'services', 'serviceCategories'));
    }

    public function create(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $serviceCategories = $this->company()->serviceCategories()->orderBy('sort_order')->get();

        return view('company.services.create', compact('branch', 'serviceCategories'));
    }

    public function store(Request $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'price'               => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency'            => ['required', 'string', 'in:' . implode(',', array_keys(config('booksy.currencies')))],
            'duration_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active'           => ['nullable', 'boolean'],
            'discount_type'       => ['nullable', 'in:percent,fixed'],
            'discount_value'      => ['nullable', 'numeric', 'min:0'],
            'discount_starts_at'  => ['nullable', 'date'],
            'discount_ends_at'    => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
        ]);

        $data['is_active']      = $request->boolean('is_active');
        $data['discount_type']  = $request->filled('discount_value') ? $request->input('discount_type') : null;
        $data['discount_value'] = $request->filled('discount_value') ? $data['discount_value'] : null;
        $data['discount_starts_at'] = $request->filled('discount_starts_at') ? $data['discount_starts_at'] : null;
        $data['discount_ends_at']   = $request->filled('discount_ends_at')   ? $data['discount_ends_at']   : null;

        $service = $branch->services()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $service->id]);
        }

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

    public function update(Request $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $data = $request->validate([
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'price'               => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency'            => ['required', 'string', 'in:' . implode(',', array_keys(config('booksy.currencies')))],
            'duration_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active'           => ['nullable', 'boolean'],
            'discount_type'       => ['nullable', 'in:percent,fixed'],
            'discount_value'      => ['nullable', 'numeric', 'min:0'],
            'discount_starts_at'  => ['nullable', 'date'],
            'discount_ends_at'    => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
        ]);

        $data['is_active']          = $request->boolean('is_active');
        $data['discount_type']      = $request->filled('discount_value') ? $request->input('discount_type') : null;
        $data['discount_value']     = $request->filled('discount_value') ? $data['discount_value'] : null;
        $data['discount_starts_at'] = $request->filled('discount_starts_at') ? $data['discount_starts_at'] : null;
        $data['discount_ends_at']   = $request->filled('discount_ends_at')   ? $data['discount_ends_at']   : null;

        $service->update($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('company.branches.services.index', $service->branch)
            ->with('success', __('Service updated successfully.'));
    }

    public function toggleActive(Request $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $service->update(['is_active' => ! $service->is_active]);

        if ($request->expectsJson()) {
            return response()->json(['is_active' => $service->is_active]);
        }

        return redirect()
            ->route('company.branches.services.index', $service->branch)
            ->with('success', __('Service status updated.'));
    }

    public function destroy(Request $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->load('branch');
        $this->authoriseService($service);

        $branch = $service->branch;
        $service->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('company.branches.services.index', $branch)
            ->with('success', __('Service deleted successfully.'));
    }
}
