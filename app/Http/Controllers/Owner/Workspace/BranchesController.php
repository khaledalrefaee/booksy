<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\Request;

/**
 * Company Workspace — Branches tab. Lists a company's branches with staff/service
 * counts and opens a per-branch drawer (working hours, gallery, QR, status toggle).
 * Reuses App\Http\Controllers\Company\BranchController logic, scoped to $company.
 */
class BranchesController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        $q = $this->searchTerm(request());

        $branches = $company->branches()
            ->withCount(['employees', 'services'])
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            }))
            ->orderByDesc('is_head_office')
            ->orderBy('sort_order')
            ->get();

        return $this->tab('branches', $company, compact('branches', 'q'));
    }

    public function detail(Company $company, Branch $branch)
    {
        abort_unless($branch->company_id === $company->id, 404);

        $branch->load([
            'workingHours' => fn ($q) => $q->orderBy('day_of_week')->orderBy('shift_number'),
            'images',
        ]);
        $branch->loadCount(['employees', 'services']);

        return $this->drawer('branch-detail', $company, compact('branch'));
    }

    public function toggleStatus(Company $company, Branch $branch, Request $request)
    {
        abort_unless($branch->company_id === $company->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $old = $branch->status;
        $branch->update(['status' => $validated['status']]);

        OwnerAudit::record('company.branch.status-update', $branch,
            old: ['status' => $old], new: ['status' => $branch->status]);

        return $this->actionOk(__('Branch status updated.'));
    }
}
