<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OwnerAuditLog;
use App\Models\Plan;
use App\Models\StaffNotification;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Broadcast announcements land in each company's in-panel notification
 * bell (StaffNotification with branch_id = null → company-wide).
 */
class AnnouncementController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->orderBy('sort_order')->orderBy('price')->get();

        $history = OwnerAuditLog::query()
            ->with('owner')
            ->where('action', 'notifications.broadcast')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('owner.announcements.index', compact('plans', 'history'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:120'],
            'body'    => ['required', 'string', 'max:500'],
            'link'    => ['nullable', 'url', 'max:255'],
            'target'  => ['required', 'string', Rule::in(['all', 'active', 'plan', 'expiring'])],
            'plan_id' => ['required_if:target,plan', 'nullable', 'integer', 'exists:plans,id'],
        ]);

        $companies = Company::query()
            ->when($validated['target'] === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($validated['target'] === 'plan', fn ($q) => $q->where('plan_id', (int) $validated['plan_id']))
            ->when($validated['target'] === 'expiring', fn ($q) => $q
                ->whereNotNull('plan_id')
                ->whereBetween('plan_expires_at', [today(), today()->addDays(7)]))
            ->get(['id']);

        if ($companies->isEmpty()) {
            return redirect()->back()->withInput()
                ->with('error', __('No companies match the selected target.'));
        }

        $now = now();

        $rows = $companies->map(fn (Company $company) => [
            'company_id' => $company->id,
            'branch_id'  => null,
            'type'       => 'platform_announcement',
            'title'      => $validated['title'],
            'body'       => $validated['body'],
            'icon'       => '📢',
            'color'      => '#C9A227',
            'link'       => $validated['link'] ?? null,
            'data'       => null,
            'read_at'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table((new StaffNotification)->getTable())->insert($rows);

        OwnerAudit::record(
            'notifications.broadcast',
            new: [
                'title'     => $validated['title'],
                'body'      => $validated['body'],
                'target'    => $validated['target'],
                'plan_id'   => $validated['plan_id'] ?? null,
                'companies' => $companies->count(),
            ],
            label: $validated['title'],
        );

        return redirect()->route('owner.announcements.index')
            ->with('success', __('Announcement sent to :count companies.', ['count' => $companies->count()]));
    }
}
