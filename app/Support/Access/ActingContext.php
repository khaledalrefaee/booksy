<?php

namespace App\Support\Access;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

/**
 * The unified "who is acting" abstraction for the company panel.
 *
 *   - Company account (guard `company`)  → ROOT actor: every permission, but still
 *     confined to its own company's branches (tenant isolation). Never affected by
 *     the employee permission system.
 *   - Employee (guard `staff`, added in Phase 4) → scoped actor: resolved through
 *     {@see PermissionResolver}.
 *
 * Controllers/policies ask the context, never a raw guard, so the same call site
 * works whether the owner or a scoped receptionist is signed in.
 */
class ActingContext
{
    private function __construct(
        public readonly bool $isRoot,
        public readonly Company $company,
        public readonly ?Employee $employee = null,
    ) {}

    public static function forCompany(Company $company): self
    {
        return new self(true, $company, null);
    }

    public static function forEmployee(Employee $employee): self
    {
        return new self(false, $employee->company, $employee);
    }

    /**
     * Resolve the acting context from the authenticated guards. A scoped staff
     * login takes precedence over the company root if both are somehow present.
     */
    public static function current(): ?self
    {
        if (array_key_exists('staff', (array) config('auth.guards')) && Auth::guard('staff')->check()) {
            return self::forEmployee(Auth::guard('staff')->user());
        }

        if (Auth::guard('company')->check()) {
            return self::forCompany(Auth::guard('company')->user());
        }

        return null;
    }

    public function companyId(): int
    {
        return (int) $this->company->id;
    }

    /** @return array<int,int> Branch ids this actor may operate in (always company-scoped). */
    public function allowedBranchIds(): array
    {
        if ($this->isRoot) {
            return $this->company->branches()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->employee->accessibleBranchIds();
    }

    public function canAccessBranch(Branch|int $branch): bool
    {
        if ($this->isRoot) {
            $branchId = $branch instanceof Branch ? (int) $branch->id : (int) $branch;

            return in_array($branchId, $this->allowedBranchIds(), true);
        }

        return $this->employee->canAccessBranch($branch);
    }

    /**
     * Effective authorization for the acting actor.
     * ROOT holds every permission but remains confined to its own branches.
     */
    public function can(string $action, Branch|int|null $branch = null): bool
    {
        if ($this->isRoot) {
            return $branch === null ? true : $this->canAccessBranch($branch);
        }

        return $this->employee->hasAbility($action, $branch);
    }
}
