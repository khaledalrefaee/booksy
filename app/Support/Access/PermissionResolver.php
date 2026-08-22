<?php

namespace App\Support\Access;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Permission;

/**
 * The single source of truth for effective employee permissions.
 *
 * Two independent axes:
 *   - WHERE (branch scope): all_branches ? every company branch : branch_employee list.
 *   - WHAT  (permission):   role defaults (L1), employee overrides (L3),
 *                           per-branch overrides (L4), and full_access.
 *
 * Decision order for a branch-level ability `can(emp, action, branch)`:
 *   1. Branch gate  — branch must be in the employee's accessible set, else DENY
 *                     (even full_access cannot bypass this; rule "Branch Access is first guard").
 *   2. L4 override  — branch_employee_permission[emp, branch, action]  (grant/deny)
 *   3. L3 override  — employee_permission[emp, action]                  (grant/deny)
 *   4. full_access ? true : role.hasPermission(action)
 *
 * Because L4/L3 are checked before full_access, an explicit `deny` always beats
 * full_access. Company-level abilities skip the branch gate entirely.
 *
 * Company isolation is inherent: the accessible branch set is always built from
 * the employee's own company, so a branch id belonging to another company is
 * never a match — cross-company access fails here, before any query runs.
 */
class PermissionResolver
{
    /** @var array<string, array{id:int, level:string}>|null Slug → {id, level}. */
    private ?array $permissionMap = null;

    /** @return array<string, array{id:int, level:string}> */
    private function permissions(): array
    {
        return $this->permissionMap ??= Permission::query()
            ->get(['id', 'slug', 'level'])
            ->mapWithKeys(fn (Permission $p) => [$p->slug => ['id' => (int) $p->id, 'level' => $p->level]])
            ->all();
    }

    public function levelOf(string $action): string
    {
        return $this->permissions()[$action]['level'] ?? 'branch';
    }

    public function isBranchLevel(string $action): bool
    {
        return $this->levelOf($action) === 'branch';
    }

    private function permissionId(string $action): ?int
    {
        return $this->permissions()[$action]['id'] ?? null;
    }

    /**
     * Branch ids this employee may operate in — always company-scoped.
     *
     * @return array<int, int>
     */
    public function allowedBranchIds(Employee $employee): array
    {
        if ($employee->all_branches) {
            return $employee->company->branches()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $employee->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
    }

    public function canAccessBranch(Employee $employee, Branch|int $branch): bool
    {
        $branchId = $branch instanceof Branch ? (int) $branch->id : (int) $branch;

        return in_array($branchId, $this->allowedBranchIds($employee), true);
    }

    /**
     * Effective permission check. $branch is required for branch-level abilities
     * and ignored for company-level ones.
     */
    public function can(Employee $employee, string $action, Branch|int|null $branch = null): bool
    {
        $branchLevel = $this->isBranchLevel($action);
        $branchId = $branch instanceof Branch ? (int) $branch->id : ($branch !== null ? (int) $branch : null);

        // 1) WHERE — branch access is the first guard.
        if ($branchLevel) {
            if ($branchId === null) {
                return false; // a branch-level ability requires a concrete branch
            }
            if (! $this->canAccessBranch($employee, $branchId)) {
                return false; // 403 — even full_access / role permission cannot cross the branch scope
            }
        }

        $permId = $this->permissionId($action);
        if ($permId === null) {
            return false; // unknown ability → no access
        }

        // 2) WHAT — most specific wins; explicit deny beats full_access.
        if ($branchLevel && $branchId !== null) {
            $l4 = $this->branchOverrideEffect($employee, $permId, $branchId);
            if ($l4 !== null) {
                return $l4 === 'grant';
            }
        }

        $l3 = $this->employeeOverrideEffect($employee, $permId);
        if ($l3 !== null) {
            return $l3 === 'grant';
        }

        // 3) full_access (all abilities) or role default. No override matched.
        if ($employee->full_access) {
            return true;
        }

        return $employee->role?->hasPermission($action) ?? false;
    }

    /** L3 — per-employee override effect for a permission, or null when none. */
    private function employeeOverrideEffect(Employee $employee, int $permId): ?string
    {
        $override = $employee->permissionOverrides->firstWhere('id', $permId);

        return $override?->pivot->effect;
    }

    /** L4 — per-branch override effect for a permission, or null when none. */
    private function branchOverrideEffect(Employee $employee, int $permId, int $branchId): ?string
    {
        $override = $employee->branchPermissionOverrides
            ->first(fn ($row) => (int) $row->permission_id === $permId && (int) $row->branch_id === $branchId);

        return $override?->effect;
    }
}
