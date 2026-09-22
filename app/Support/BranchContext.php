<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;

/**
 * Branch Context — the company dashboard works either across ALL branches or
 * inside ONE branch. The choice is a per-session preference (never a URL or a
 * DB column), always validated against the branches the company actually owns,
 * so it can never point at a branch the user may not reach.
 *
 * null = "All Branches". A Branch instance = that specific branch.
 */
class BranchContext
{
    private const KEY = 'company_branch_context';

    /** In-request memo so repeated reads don't re-query. */
    private ?array $memo = null;

    /** The current context branch id, or null for "All Branches". */
    public function currentId(Company $company): ?int
    {
        return $this->resolve($company)['id'];
    }

    /** The current context Branch, or null for "All Branches". */
    public function current(Company $company): ?Branch
    {
        return $this->resolve($company)['branch'];
    }

    /** Every branch the company can switch between (sidebar + selector). */
    public function accessibleBranches(Company $company)
    {
        return $company->branches()->orderBy('sort_order')->orderBy('name_en')->get();
    }

    /**
     * Store a new context. `$value` is a branch id or the literal 'all'.
     * A branch the company does not own is silently ignored (stays as-is).
     */
    public function set(Company $company, string $value): void
    {
        $this->memo = null;

        if ($value === 'all') {
            session([self::KEY => 'all']);
            return;
        }

        if (ctype_digit($value) && $this->owns($company, (int) $value)) {
            session([self::KEY => (int) $value]);
        }
    }

    /** Resolve once per request: reads the session, validates, applies defaults. */
    private function resolve(Company $company): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $stored = session(self::KEY);

        // Explicit "All Branches".
        if ($stored === 'all') {
            return $this->memo = ['id' => null, 'branch' => null];
        }

        // A stored branch that is still owned by this company.
        if ($stored !== null && ctype_digit((string) $stored)) {
            $branch = $company->branches()->find((int) $stored);
            if ($branch) {
                return $this->memo = ['id' => $branch->id, 'branch' => $branch];
            }
        }

        // No valid choice yet. A single-branch company lands inside its branch;
        // a multi-branch company starts on "All Branches".
        $branches = $company->branches()->orderBy('sort_order')->get();
        if ($branches->count() === 1) {
            $only = $branches->first();
            return $this->memo = ['id' => $only->id, 'branch' => $only];
        }

        return $this->memo = ['id' => null, 'branch' => null];
    }

    private function owns(Company $company, int $branchId): bool
    {
        return $company->branches()->whereKey($branchId)->exists();
    }
}
