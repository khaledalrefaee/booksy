<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\BookingPolicy;
use App\Models\Company;
use App\Models\OwnerAuditLog;
use App\Models\Plan;

/**
 * Company Workspace — Settings tab. Folds in the company's subscription editor
 * (existing owner.companies.update-subscription endpoint), booking policy,
 * resources, subscription-payment history and the per-company audit trail.
 */
class SettingsController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        $company->loadMissing('plan');

        $plans          = Plan::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();
        $featureCatalog = Plan::featureCatalog();

        $companyPolicy = $company->bookingPolicies()->whereNull('branch_id')->first()
            ?? new BookingPolicy(BookingPolicy::defaults());

        $resources = \App\Models\Resource::query()
            ->whereIn('branch_id', $company->branches()->select('id'))
            ->with('branch')
            ->orderBy('sort_order')
            ->get();

        $payments = $company->subscriptionPayments()
            ->latest('paid_at')->latest('id')
            ->limit(30)
            ->get();

        $auditLogs = OwnerAuditLog::query()
            ->with('owner')
            ->where('auditable_type', $company->getMorphClass())
            ->where('auditable_id', $company->id)
            ->latest('id')
            ->limit(30)
            ->get();

        return $this->tab('settings', $company, compact(
            'plans', 'featureCatalog', 'companyPolicy', 'resources', 'payments', 'auditLogs'
        ));
    }
}
