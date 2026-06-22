<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\BranchImage;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    public function index(Request $request): View
    {
        $company   = $this->company();
        $branches  = $company->branches()->orderBy('sort_order')->get();
        $branchIds = $branches->pluck('id');

        $filterBranchId = $request->query('branch_id') ? (int) $request->query('branch_id') : null;
        $activeTab      = $request->query('tab', 'employees');

        // Employees query
        $employeeQuery = Employee::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'role', 'compensation'])
            ->withCount([
                'appointments as appointments_this_month' => fn ($q) => $q
                    ->whereMonth('start_time', now()->month)
                    ->whereYear('start_time', now()->year),
            ]);

        if ($filterBranchId) {
            $employeeQuery->where('branch_id', $filterBranchId);
        } else {
            $employeeQuery->whereIn('branch_id', $branchIds);
        }

        $employees = $employeeQuery->orderBy('name_en')->get();

        // Services query
        $serviceQuery = Service::query()
            ->with(['branch', 'serviceCategory'])
            ->orderBy('name_en');

        if ($filterBranchId) {
            $serviceQuery->where('branch_id', $filterBranchId);
        } else {
            $serviceQuery->whereIn('branch_id', $branchIds);
        }

        $services = $serviceQuery->get();

        // Gallery images
        $imageQuery = BranchImage::query()
            ->with('branch')
            ->orderBy('branch_id')
            ->orderBy('sort_order');

        if ($filterBranchId) {
            $imageQuery->where('branch_id', $filterBranchId);
        } else {
            $imageQuery->whereIn('branch_id', $branchIds);
        }

        $images = $imageQuery->get();

        return view('company.staff.index', compact(
            'branches', 'employees', 'services', 'images', 'filterBranchId', 'activeTab'
        ));
    }
}
