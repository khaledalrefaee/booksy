<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Support\Access\PermissionResolver;

/**
 * Branch-scoped authorization helpers for the Employee model. Thin delegation to
 * {@see PermissionResolver} so the actual logic stays in one testable place.
 *
 * Named hasAbility() rather than can() so it never collides with the Gate's
 * Authorizable::can($ability, $arguments) contract when Employee becomes an
 * Authenticatable in Phase 4.
 */
trait HasBranchScopedAccess
{
    /** @return array<int,int> Branch ids this employee may operate in (company-scoped). */
    public function accessibleBranchIds(): array
    {
        return app(PermissionResolver::class)->allowedBranchIds($this);
    }

    public function canAccessBranch(Branch|int $branch): bool
    {
        return app(PermissionResolver::class)->canAccessBranch($this, $branch);
    }

    /** WHAT + WHERE. Branch is required for branch-level abilities, ignored for company-level. */
    public function hasAbility(string $action, Branch|int|null $branch = null): bool
    {
        return app(PermissionResolver::class)->can($this, $action, $branch);
    }
}
