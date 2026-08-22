<?php

namespace Tests\Feature\Access;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

/**
 * Phase 3.1 — Owner employee assignment uses the SAME EmployeeAccessWriter and
 * PermissionCatalog as the company dashboard, so owner-created/edited employees
 * get identical L2/L3/L4 records and effective permissions.
 */
class OwnerEmployeeAssignmentTest extends TestCase
{
    private Company $company;
    private Branch $damascus;
    private Branch $aleppo;
    private Branch $homs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsOwner();
        $this->company  = Company::factory()->create();
        $this->damascus = Branch::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Damascus']);
        $this->aleppo   = Branch::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Aleppo']);
        $this->homs     = Branch::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Homs']);
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    /** One-row bulk-store payload; override the row per test. */
    private function bulkPayload(array $row = []): array
    {
        return [
            'employees' => [array_merge([
                'name_en'   => 'Owner Emp',
                'name_ar'   => 'موظف',
                'email'     => 'oe'.uniqid().'@example.com',
                'password'  => 'password123',
                'role_id'   => $this->roleId('reception'),
                'is_active' => 1,
            ], $row)],
        ];
    }

    private function store(array $payload, ?Branch $branch = null)
    {
        return $this->post(route('owner.branches.employees.store', $branch ?? $this->damascus), $payload);
    }

    private function latest(): Employee
    {
        return Employee::where('company_id', $this->company->id)->latest('id')->first()
            ->fresh(['branches', 'role', 'permissionOverrides', 'branchPermissionOverrides', 'company']);
    }

    private function updatePayload(array $extra = []): array
    {
        return array_merge([
            'name_en'     => 'Owner Emp',
            'name_ar'     => 'موظف',
            'role_id'     => $this->roleId('reception'),
            'access_mode' => 'selected',
            'branch_ids'  => [$this->damascus->id],
            'is_active'   => 1,
        ], $extra);
    }

    // ── create: single branch (default wizard behaviour) ──
    public function test_owner_creates_employee_with_single_branch(): void
    {
        $this->store($this->bulkPayload())->assertRedirect();

        $emp = $this->latest();
        $this->assertEqualsCanonicalizing([$this->damascus->id], $emp->branches->pluck('id')->all());
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
        // Writes the same L2 record the company controller would.
        $this->assertDatabaseHas('branch_employee', ['employee_id' => $emp->id, 'branch_id' => $this->damascus->id]);
    }

    // ── create: multiple branches ──
    public function test_owner_creates_employee_with_multiple_branches(): void
    {
        $this->store($this->bulkPayload(['branch_ids' => [$this->damascus->id, $this->aleppo->id]]))->assertRedirect();

        $emp = $this->latest();
        $this->assertEqualsCanonicalizing([$this->damascus->id, $this->aleppo->id], $emp->branches->pluck('id')->all());
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->homs));
    }

    // ── create: all branches ──
    public function test_owner_creates_all_branches_employee(): void
    {
        $this->store($this->bulkPayload(['access_mode' => 'all']))->assertRedirect();

        $emp = $this->latest();
        $this->assertTrue($emp->all_branches);
        foreach ([$this->damascus, $this->aleppo, $this->homs] as $b) {
            $this->assertTrue($emp->hasAbility('appointments.manage', $b));
        }
    }

    // ── create: full access ──
    public function test_owner_creates_full_access_employee(): void
    {
        $this->store($this->bulkPayload(['full_access' => 1]))->assertRedirect();

        $emp = $this->latest();
        $this->assertTrue($emp->full_access);
        $this->assertTrue($emp->hasAbility('cash.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('cash.manage', $this->aleppo)); // branch scope still applies
    }

    // ── create: company isolation (foreign branch ignored) ──
    public function test_owner_store_ignores_foreign_branch(): void
    {
        $foreign = Branch::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->store($this->bulkPayload(['branch_ids' => [$this->damascus->id, $foreign->id]]))->assertRedirect();

        $emp = $this->latest();
        $this->assertEqualsCanonicalizing([$this->damascus->id], $emp->branches->pluck('id')->all());
        $this->assertFalse($emp->canAccessBranch($foreign));
    }

    // ── edit page renders + update modifies permissions (L3) ──
    public function test_owner_edit_page_renders(): void
    {
        $emp = $this->makeEmployee();
        $this->get(route('owner.employees.edit', $emp))->assertOk();
    }

    public function test_owner_update_applies_advanced_l3_override(): void
    {
        $emp = $this->makeEmployee();

        $this->put(route('owner.employees.update', $emp), $this->updatePayload([
            'overrides' => ['customers' => 'none'],
        ]))->assertRedirect();

        $emp->refresh();
        $this->assertFalse($emp->hasAbility('customers.manage', $this->damascus));
        $this->assertDatabaseHas('employee_permission', [
            'employee_id'   => $emp->id,
            'permission_id' => Permission::where('slug', 'customers.manage')->value('id'),
            'effect'        => 'deny',
        ]);
    }

    // ── update: branch-specific override (L4) ──
    public function test_owner_update_applies_l4_branch_override(): void
    {
        $emp = $this->makeEmployee();

        $this->put(route('owner.employees.update', $emp), $this->updatePayload([
            'branch_ids'       => [$this->damascus->id, $this->aleppo->id],
            'per_branch'       => 1,
            'branch_overrides' => [$this->aleppo->id => ['appointments' => 'view']],
        ]))->assertRedirect();

        $emp->refresh();
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertTrue($emp->hasAbility('appointments.view', $this->aleppo));
    }

    // ── update: modify branches ──
    public function test_owner_update_modifies_branches(): void
    {
        $emp = $this->makeEmployee();

        $this->put(route('owner.employees.update', $emp), $this->updatePayload([
            'branch_ids' => [$this->damascus->id, $this->aleppo->id],
        ]))->assertRedirect();

        $emp = $emp->fresh(['branches']);
        $this->assertEqualsCanonicalizing([$this->damascus->id, $this->aleppo->id], $emp->branches->pluck('id')->all());
    }

    // ── update: cannot reach another company's branch ──
    public function test_owner_update_rejects_foreign_branch(): void
    {
        $emp = $this->makeEmployee();
        $foreign = Branch::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->put(route('owner.employees.update', $emp), $this->updatePayload([
            'branch_ids' => [$this->damascus->id, $foreign->id],
        ]))->assertRedirect();

        $emp->refresh();
        $this->assertFalse($emp->canAccessBranch($foreign));
        $this->assertEqualsCanonicalizing([$this->damascus->id], $emp->branches()->pluck('branches.id')->all());
    }

    private function makeEmployee(): Employee
    {
        $emp = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id'  => $this->damascus->id,
            'role_id'    => $this->roleId('reception'),
        ]);
        $emp->branches()->sync([$this->damascus->id]);

        return $emp->fresh(['branches', 'role', 'company']);
    }
}
