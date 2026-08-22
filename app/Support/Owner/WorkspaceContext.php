<?php

namespace App\Support\Owner;

use App\Models\Company;
use Illuminate\Support\Facades\Gate;

/**
 * View-side helper handed to every Company Workspace tab/drawer partial as $ws.
 * Keeps tab blades terse and consistent: scoped route URLs, feature-gate checks,
 * money formatting, and the "open full editor" (impersonation) contract.
 */
class WorkspaceContext
{
    public function __construct(public Company $company)
    {
    }

    /** URL for a workspace tab endpoint, e.g. $ws->url('finance'). */
    public function url(string $tab, array $params = []): string
    {
        return route("owner.companies.ws.$tab", array_merge(['company' => $this->company->id], $params));
    }

    /** Does this company's plan (+ overrides + active subscription) include a feature? */
    public function feature(string $key): bool
    {
        return $this->company->hasFeature($key);
    }

    /** Owner permission check (mirror of @can('owner-can', $key)). */
    public function can(string $key): bool
    {
        return Gate::allows('owner-can', $key);
    }

    /** Money in the company's own currency (falls back to app currency). */
    public function money(float|int|string|null $amount): string
    {
        $currency = $this->company->currency ?? config('app.currency', '');

        return number_format((float) $amount, 2) . ($currency ? " {$currency}" : '');
    }

    /** Impersonation form action — the "Open full editor" deep link target. */
    public function fullEditorAction(): string
    {
        return route('owner.companies.impersonate', $this->company);
    }
}
