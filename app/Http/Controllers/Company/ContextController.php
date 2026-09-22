<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContextController extends Controller
{
    /**
     * Switch the active branch context, then return the user to a sensible place:
     *   • a company-wide page they were on stays put (now reflecting the branch);
     *   • a page whose URL is tied to a specific branch (…/branches/{id}/…) would
     *     be stale, so we send them to the new context's home instead
     *     (branch overview for a branch, aggregate dashboard for All Branches).
     */
    public function switch(Request $request, BranchContext $context): RedirectResponse
    {
        /** @var \App\Models\Company $company */
        $company = Auth::guard('company')->user();

        $context->set($company, (string) $request->query('to', 'all'));

        $from = (string) $request->query('from', '');
        // A branch-scoped URL (…/branches/{id}/…) would go stale, and the
        // dashboard shows the aggregate — both should jump to the new context's
        // home instead of staying put. Every other company page stays.
        $branchScoped = (bool) preg_match('#/company/branches/\d+#', $from);
        $isDashboard  = (bool) preg_match('#/company/dashboard/?($|\?)#', $from);

        if ($from !== '' && str_contains($from, '/company/') && ! $branchScoped && ! $isDashboard) {
            // Drop any explicit branch_id so the fresh context takes effect.
            return redirect()->to($this->stripBranchId($from));
        }

        $branch = $context->current($company);

        return $branch
            ? redirect()->route('company.branches.show', $branch)
            : redirect()->route('company.dashboard');
    }

    private function stripBranchId(string $url): string
    {
        $parts = parse_url($url);
        $path  = $parts['path'] ?? '/';

        parse_str($parts['query'] ?? '', $query);
        unset($query['branch_id'], $query['branch']);

        return $path . (empty($query) ? '' : ('?' . http_build_query($query)));
    }
}
