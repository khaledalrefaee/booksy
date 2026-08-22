<?php

namespace Tests\Feature\Access;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Access\ActingContext;
use Tests\TestCase;

/**
 * Phase 2 — Permission Engine. Covers the agreed Definition-of-Done cases:
 * branch scope (WHERE), permissions (WHAT), full_access ≠ all_branches,
 * explicit deny > full_access, company isolation, and ROOT immunity.
 */
class PermissionEngineTest extends TestCase
{
    private Company $companyA;
    private Company $companyB;
    private Branch $damascus;
    private Branch $aleppo;
    private Branch $homs;
    private Branch $branchB;   // belongs to company B

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();

        $this->damascus = Branch::factory()->create(['company_id' => $this->companyA->id, 'name_en' => 'Damascus']);
        $this->aleppo   = Branch::factory()->create(['company_id' => $this->companyA->id, 'name_en' => 'Aleppo']);
        $this->homs     = Branch::factory()->create(['company_id' => $this->companyA->id, 'name_en' => 'Homs']);
        $this->branchB  = Branch::factory()->create(['company_id' => $this->companyB->id, 'name_en' => 'Company B branch']);
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    private function permId(string $slug): int
    {
        return Permission::where('slug', $slug)->firstOrFail()->id;
    }

    /** @param array<int> $branchIds */
    private function employee(string $roleSlug, array $branchIds = [], bool $allBranches = false, bool $fullAccess = false): Employee
    {
        $emp = Employee::factory()->create([
            'company_id'   => $this->companyA->id,
            'branch_id'    => $branchIds[0] ?? null,
            'role_id'      => $this->roleId($roleSlug),
            'all_branches' => $allBranches,
            'full_access'  => $fullAccess,
        ]);

        if (! $allBranches) {
            $emp->branches()->sync($branchIds);
        }

        return $emp->fresh(['branches', 'role', 'permissionOverrides', 'branchPermissionOverrides', 'company']);
    }

    // ── Case 1: Receptionist + Damascus only ────────────────────────────────
    public function test_receptionist_single_branch_is_confined_to_that_branch(): void
    {
        $emp = $this->employee('reception', [$this->damascus->id]);

        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->homs));
    }

    // ── Case 2: Receptionist + Damascus + Aleppo ────────────────────────────
    public function test_receptionist_two_branches_manages_both_not_third(): void
    {
        $emp = $this->employee('reception', [$this->damascus->id, $this->aleppo->id]);

        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->homs));
    }

    // ── Case 3: Receptionist + All Branches ─────────────────────────────────
    public function test_receptionist_all_branches_manages_every_branch(): void
    {
        $emp = $this->employee('reception', allBranches: true);

        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->homs));

        // ...but reception does NOT gain unrelated permissions just from all_branches.
        $this->assertFalse($emp->hasAbility('cash.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('billing.manage')); // company-level
    }

    // ── Case 4: Full Access + Damascus only ─────────────────────────────────
    public function test_full_access_single_branch_does_everything_in_that_branch_only(): void
    {
        $emp = $this->employee('reception', [$this->damascus->id], fullAccess: true);

        // Everything in Damascus:
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertTrue($emp->hasAbility('cash.manage', $this->damascus));
        $this->assertTrue($emp->hasAbility('inventory.manage', $this->damascus));

        // Company-level abilities: full_access grants them (no branch gate).
        $this->assertTrue($emp->hasAbility('billing.manage'));
        $this->assertTrue($emp->hasAbility('settings.company'));

        // But NOT in another branch — branch scope still blocks (full_access ≠ all_branches).
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertFalse($emp->hasAbility('cash.manage', $this->homs));
    }

    // ── Full Access + All Branches ──────────────────────────────────────────
    public function test_full_access_all_branches_does_everything_everywhere(): void
    {
        $emp = $this->employee('reception', allBranches: true, fullAccess: true);

        foreach ([$this->damascus, $this->aleppo, $this->homs] as $b) {
            $this->assertTrue($emp->hasAbility('appointments.manage', $b));
            $this->assertTrue($emp->hasAbility('cash.manage', $b));
        }
        $this->assertTrue($emp->hasAbility('billing.manage'));
    }

    // ── Case 5 (explicit deny beats full_access) + Ahmed scenario ───────────
    public function test_explicit_deny_overrides_full_access(): void
    {
        $emp = $this->employee('reception', [$this->damascus->id], fullAccess: true);

        // L4 deny cash.manage in Damascus only.
        $emp->branchPermissionOverrides()->create([
            'branch_id'     => $this->damascus->id,
            'permission_id' => $this->permId('cash.manage'),
            'effect'        => 'deny',
        ]);
        $emp->refresh();

        $this->assertFalse($emp->hasAbility('cash.manage', $this->damascus)); // deny wins over full_access
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus)); // still allowed
    }

    public function test_l3_employee_deny_beats_full_access_across_branches(): void
    {
        $emp = $this->employee('reception', [$this->damascus->id, $this->aleppo->id], fullAccess: true);

        $emp->permissionOverrides()->attach($this->permId('customers.manage'), ['effect' => 'deny']);
        $emp->refresh();

        $this->assertFalse($emp->hasAbility('customers.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('customers.manage', $this->aleppo));
        // unrelated ability unaffected
        $this->assertTrue($emp->hasAbility('cash.manage', $this->damascus));
    }

    public function test_ahmed_different_permission_per_branch(): void
    {
        // Damascus = Manage, Aleppo = View only, Homs = No access.
        $emp = $this->employee('reception', [$this->damascus->id, $this->aleppo->id]);
        $emp->branchPermissionOverrides()->create([
            'branch_id'     => $this->aleppo->id,
            'permission_id' => $this->permId('appointments.manage'),
            'effect'        => 'deny',
        ]);
        $emp->refresh();

        // Damascus — full manage
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertTrue($emp->hasAbility('appointments.view', $this->damascus));
        // Aleppo — view only, manage denied
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertTrue($emp->hasAbility('appointments.view', $this->aleppo));
        // Homs — no access at all (not in branch list)
        $this->assertFalse($emp->hasAbility('appointments.view', $this->homs));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->homs));
    }

    // ── Case 6: access to a branch not allowed ──────────────────────────────
    public function test_branch_gate_blocks_unassigned_branch_even_with_permission(): void
    {
        $emp = $this->employee('reception', [$this->damascus->id]);

        $this->assertTrue($emp->role->hasPermission('appointments.manage')); // has the WHAT
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo)); // but not the WHERE
        $this->assertFalse($emp->canAccessBranch($this->aleppo));
    }

    // ── Case 7: company isolation ───────────────────────────────────────────
    public function test_company_isolation_blocks_foreign_company_branch(): void
    {
        $emp = $this->employee('reception', allBranches: true, fullAccess: true);

        // Even all_branches + full_access cannot reach another company's branch.
        $this->assertFalse($emp->canAccessBranch($this->branchB));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->branchB));
        $this->assertNotContains($this->branchB->id, $emp->accessibleBranchIds());
    }

    // ── Case 8: Owner / Company account is ROOT ─────────────────────────────
    public function test_company_root_actor_has_full_access_but_stays_isolated(): void
    {
        $root = ActingContext::forCompany($this->companyA);

        $this->assertTrue($root->isRoot);
        $this->assertTrue($root->can('appointments.manage', $this->damascus));
        $this->assertTrue($root->can('cash.manage', $this->aleppo));
        $this->assertTrue($root->can('billing.manage')); // company-level
        $this->assertTrue($root->can('settings.company'));

        // ...yet still confined to its own company's branches.
        $this->assertFalse($root->can('appointments.manage', $this->branchB));
        $this->assertFalse($root->canAccessBranch($this->branchB));
    }

    public function test_root_is_unaffected_by_employee_overrides(): void
    {
        // An employee deny must never touch the ROOT actor.
        $emp = $this->employee('reception', [$this->damascus->id], fullAccess: true);
        $emp->branchPermissionOverrides()->create([
            'branch_id'     => $this->damascus->id,
            'permission_id' => $this->permId('cash.manage'),
            'effect'        => 'deny',
        ]);

        $root = ActingContext::forCompany($this->companyA);
        $this->assertTrue($root->can('cash.manage', $this->damascus));
    }

    // ── Company-level vs branch-level distinction ───────────────────────────
    public function test_company_level_ability_skips_branch_gate(): void
    {
        $finance = $this->employee('finance', [$this->damascus->id]);

        // finance role holds finance.view_company (company-level) — no branch needed.
        $this->assertTrue($finance->hasAbility('finance.view_company'));
        // but a branch-level cash ability is confined to its branch.
        $this->assertTrue($finance->hasAbility('cash.view', $this->damascus));
        $this->assertFalse($finance->hasAbility('cash.view', $this->aleppo));
    }
}
