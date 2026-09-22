<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the current Branch Context with every company view so the sidebar,
 * header and pages all agree on which branch is active — without any controller
 * having to fetch it. Registered on the authenticated company route group.
 */
class ShareBranchContext
{
    public function __construct(private readonly BranchContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Company|null $company */
        $company = Auth::guard('company')->user();

        if ($company) {
            $branchId = $this->context->currentId($company);

            View::share('branchContextBranches', $this->context->accessibleBranches($company));
            View::share('branchContext',   $this->context->current($company));   // Branch|null
            View::share('branchContextId', $branchId);                           // int|null

            $this->applyDefaultBranchFilter($request, $branchId);
        }

        return $next($request);
    }

    /**
     * The single source of truth for branch filtering.
     *
     * When a specific branch is the active context, inject it as the default
     * branch filter on EVERY company GET page that hasn't been given one
     * explicitly. Every controller/query that reads `branch_id` (or, on the
     * booking-sources page, `branch`) then filters by the selected branch with
     * no per-page logic of its own — which is what stops pages disagreeing.
     *
     * Rules:
     *   • Specific branch context → inject (unless the user passed the param).
     *   • All Branches (null)      → inject nothing; pages aggregate / keep their
     *                                own visible filter.
     *   • Explicit param (incl. an empty "all") always wins.
     */
    private function applyDefaultBranchFilter(Request $request, ?int $branchId): void
    {
        if ($branchId === null || ! $request->isMethod('get')) {
            return;
        }

        $routeName = optional($request->route())->getName();

        if (! $routeName
            || ! str_starts_with($routeName, 'company.')
            || $routeName === 'company.context.switch') {
            return;
        }

        // The booking-sources page keys its selector on `branch`; everything
        // else uses the conventional `branch_id`.
        $param = $routeName === 'company.marketing.booking-sources' ? 'branch' : 'branch_id';

        if (! $request->has($param)) {
            // Sets query() + input() so both the query and the filter UI see it.
            $request->query->set($param, (string) $branchId);
        }
    }
}
