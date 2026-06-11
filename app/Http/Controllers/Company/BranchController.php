<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BranchController extends Controller
{
    private function company(): \App\Models\Company
    {
        /** @var \App\Models\Company */
        return Auth::guard('company')->user();
    }

    public function index(): View
    {
        $branches = $this->company()
            ->branches()
            ->orderBy('sort_order')
            ->get();

        return view('company.branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('company.branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en'        => ['required', 'string', 'max:255'],
            'name_ar'        => ['nullable', 'string', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'is_head_office' => ['boolean'],
            'status'         => ['required', 'in:active,inactive,maintenance'],
        ]);

        $company = $this->company();

        if (! empty($data['is_head_office'])) {
            $company->branches()->update(['is_head_office' => false]);
        }

        $company->branches()->create([
            'name_en'        => $data['name_en'],
            'name_ar'        => $data['name_ar'] ?? null,
            'address'        => $data['address'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'is_head_office' => ! empty($data['is_head_office']),
            'status'         => $data['status'],
            'sort_order'     => $company->branches()->count(),
        ]);

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch created.'));
    }

    public function edit(Branch $branch): View
    {
        $this->authoriseBranch($branch);

        $socialLinks = $branch->socialLinks()->get()->keyBy('platform');

        return view('company.branches.edit', compact('branch', 'socialLinks'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $data = $request->validate([
            'name_en'        => ['required', 'string', 'max:255'],
            'name_ar'        => ['nullable', 'string', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'is_head_office' => ['boolean'],
            'status'         => ['required', 'in:active,inactive,maintenance'],
            'social_links'   => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:2048'],
        ]);

        if (! empty($data['is_head_office'])) {
            $this->company()->branches()->where('id', '!=', $branch->id)->update(['is_head_office' => false]);
        }

        $branch->update($data);

        // Sync social links
        SocialLink::syncFor($branch, $request->input('social_links', []));

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch updated.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);
        $branch->delete();

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch deleted.'));
    }

    /** Quick status toggle from the branch list */
    public function updateStatus(Request $request, Branch $branch): RedirectResponse
    {
        $this->authoriseBranch($branch);

        $request->validate([
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $branch->update(['status' => $request->status]);

        return redirect()->route('company.branches.index')
            ->with('success', __('Branch status updated.'));
    }

    private function authoriseBranch(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->company()->id, 403);
    }
}
