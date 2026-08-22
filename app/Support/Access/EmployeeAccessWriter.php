<?php

namespace App\Support\Access;

use App\Models\Employee;
use Illuminate\Support\Collection;

/**
 * Persists an employee's branch access + permission overrides from the employee
 * form. The single write path for L2 (branch access), L3 (employee overrides) and
 * L4 (per-branch overrides), used by both create and update so the two never drift.
 *
 * The form speaks in friendly module levels (default/none/view/manage); this class
 * translates them into concrete grant/deny rows. "default" writes nothing, keeping
 * the common case override-free — the engine then falls back to role defaults.
 */
class EmployeeAccessWriter
{
    public function __construct(private PermissionCatalog $catalog) {}

    /**
     * @param  array<string,mixed>  $input   validated form input
     * @param  Collection<int,int>  $companyBranchIds  ids the company actually owns
     */
    public function apply(Employee $employee, array $input, Collection $companyBranchIds): void
    {
        $allBranches = ($input['access_mode'] ?? 'selected') === 'all';

        $branchIds = $allBranches
            ? []
            : collect($input['branch_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $companyBranchIds->contains($id))
                ->unique()
                ->values()
                ->all();

        // ── L2: flags + home branch + branch access set ──
        $employee->all_branches = $allBranches;
        $employee->full_access  = (bool) ($input['full_access'] ?? false);
        $employee->branch_id = $allBranches
            ? null
            : (in_array((int) $employee->branch_id, $branchIds, true) ? $employee->branch_id : ($branchIds[0] ?? null));
        $employee->save();

        $employee->branches()->sync($allBranches ? [] : $branchIds);

        // ── L3: employee-level overrides (apply across all the employee's branches) ──
        $employee->permissionOverrides()->sync(
            $this->overrideRows($input['overrides'] ?? [])
        );

        // ── L4: per-branch overrides (advanced, only for branches in the access set) ──
        $employee->branchPermissionOverrides()->delete();

        if (! empty($input['per_branch']) && ! $allBranches) {
            foreach ($input['branch_overrides'] ?? [] as $branchId => $moduleLevels) {
                $branchId = (int) $branchId;
                if (! in_array($branchId, $branchIds, true)) {
                    continue; // never write an override for a branch the employee can't reach
                }
                foreach ($this->effectsFor($moduleLevels) as $permId => $effect) {
                    $employee->branchPermissionOverrides()->create([
                        'branch_id'     => $branchId,
                        'permission_id' => $permId,
                        'effect'        => $effect,
                    ]);
                }
            }
        }
    }

    /**
     * L3 sync payload: permission_id => ['effect' => grant|deny].
     *
     * @param  array<string,string>  $moduleLevels  moduleKey => default|none|view|manage
     * @return array<int, array{effect:string}>
     */
    private function overrideRows(array $moduleLevels): array
    {
        $rows = [];
        foreach ($this->effectsFor($moduleLevels) as $permId => $effect) {
            $rows[$permId] = ['effect' => $effect];
        }

        return $rows;
    }

    /**
     * Translate friendly module levels into concrete permission effects.
     *
     * @param  array<string,string>  $moduleLevels  moduleKey => default|none|view|manage
     * @return array<int,string>  permissionId => grant|deny
     */
    private function effectsFor(array $moduleLevels): array
    {
        $slugToId = $this->catalog->slugToId();
        $byKey = collect(PermissionCatalog::MODULES)->keyBy('key');
        $effects = [];

        foreach ($moduleLevels as $key => $level) {
            $module = $byKey->get($key);
            if (! $module || $level === PermissionCatalog::LEVEL_DEFAULT || $level === null || $level === '') {
                continue;
            }

            $view   = $module['view'] ? ($slugToId[$module['view']] ?? null) : null;
            $manage = $module['manage'] ? ($slugToId[$module['manage']] ?? null) : null;

            switch ($level) {
                case PermissionCatalog::LEVEL_NONE:
                    if ($view)   $effects[$view]   = 'deny';
                    if ($manage) $effects[$manage] = 'deny';
                    break;
                case PermissionCatalog::LEVEL_VIEW:
                    if ($view)   $effects[$view]   = 'grant';
                    if ($manage) $effects[$manage] = 'deny';
                    break;
                case PermissionCatalog::LEVEL_MANAGE:
                    if ($view)   $effects[$view]   = 'grant';
                    if ($manage) $effects[$manage] = 'grant';
                    break;
            }
        }

        return $effects;
    }
}
