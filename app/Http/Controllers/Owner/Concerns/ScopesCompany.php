<?php

namespace App\Http\Controllers\Owner\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Shared helpers for owner "Company Workspace" tab controllers.
 *
 * Every workspace tab is an owner-namespace controller that renders a
 * self-contained partial for a single {company}, reusing the tenant-side
 * models/queries but scoped by company_id. This trait centralises that
 * scoping plus the standard partial-response conventions so all tabs behave
 * identically for the shell's lazy loader and drawer system.
 */
trait ScopesCompany
{
    /**
     * Render a workspace tab partial with the shared shell context baked in.
     *
     * Tab partials live in resources/views/owner/companies/tabs/*.blade.php and
     * are returned WITHOUT the layout — the shell injects the HTML via fetch().
     * Always receive $company and $ws (shell helpers) so the partial can build
     * scoped routes, feature-lock notices, and "open full editor" deep links.
     */
    protected function tab(string $view, Company $company, array $data = [])
    {
        return view("owner.companies.tabs.$view", array_merge([
            'company' => $company,
            'ws'      => new \App\Support\Owner\WorkspaceContext($company),
        ], $data));
    }

    /**
     * Render a drawer partial (also layout-less) for the shell drawer system.
     */
    protected function drawer(string $view, Company $company, array $data = [])
    {
        return view("owner.companies.drawers.$view", array_merge([
            'company' => $company,
            'ws'      => new \App\Support\Owner\WorkspaceContext($company),
        ], $data));
    }

    /**
     * Standard JSON envelope for inline actions (POST/PATCH/DELETE) so the
     * shell can toast + refresh the current tab uniformly.
     */
    protected function actionOk(string $message, array $extra = [])
    {
        return response()->json(array_merge(['ok' => true, 'message' => $message], $extra));
    }

    protected function actionFail(string $message, int $status = 422)
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }

    /**
     * Pull a normalised search term from the request (shared toolbar contract).
     */
    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->input('q', ''));
    }
}
