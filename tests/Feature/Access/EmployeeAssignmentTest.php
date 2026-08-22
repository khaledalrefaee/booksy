<?php

namespace Tests\Feature\Access;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

/**
 * Phase 3 — Assignment UI backend. Drives the employee create/update endpoints
 * exactly as the form does, then asserts the persisted access (L2/L3/L4) and the
 * effective permissions computed by the Phase 2 engine.
 */
class EmployeeAssignmentTest extends TestCase
{
    private Company $company;
    private Branch $damascus;
    private Branch $aleppo;
    private Branch $homs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company  = Company::factory()->create();
        $this->damascus = Branch::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Damascus']);
        $this->aleppo   = Branch::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Aleppo']);
        $this->homs     = Branch::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Homs']);
        $this->actingAsCompany($this->company);
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    /** Minimal valid create payload; override the access bits per test. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_en'      => 'Ahmed',
            'name_ar'      => 'أحمد',
            'email'        => 'ahmed'.uniqid().'@example.com',
            'dial_code'    => '+963',
            'phone_number' => '941'.random_int(100000, 999999),
            'role_id'      => $this->roleId('reception'),
            'password'     => 'password123',
            'is_active'    => 1,
            'access_mode'  => 'selected',
            'branch_ids'   => [$this->damascus->id],
        ], $overrides);
    }

    private function store(array $payload, ?Branch $branch = null)
    {
        return $this->post(route('company.branches.employees.store', $branch ?? $this->damascus), $payload);
    }

    private function latestEmployee(): Employee
    {
        return Employee::where('company_id', $this->company->id)->latest('id')->first()
            ->fresh(['branches', 'role', 'permissionOverrides', 'branchPermissionOverrides', 'company']);
    }

    // ── the create/edit pages still render ──
    public function test_create_and_edit_pages_render(): void
    {
        $this->get(route('company.branches.employees.create', $this->damascus))->assertOk();

        $emp = Employee::factory()->create(['company_id' => $this->company->id, 'branch_id' => $this->damascus->id, 'role_id' => $this->roleId('reception')]);
        $emp->branches()->sync([$this->damascus->id]);
        $this->get(route('company.employees.edit', $emp))->assertOk();
    }

    // ── Role + selected branch ──
    public function test_create_role_with_selected_branch(): void
    {
        $this->store($this->payload())->assertRedirect();

        $emp = $this->latestEmployee();
        $this->assertFalse($emp->all_branches);
        $this->assertEqualsCanonicalizing([$this->damascus->id], $emp->branches->pluck('id')->all());
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
    }

    // ── Role + All Branches ──
    public function test_create_role_with_all_branches(): void
    {
        $this->store($this->payload(['access_mode' => 'all', 'branch_ids' => []]))->assertRedirect();

        $emp = $this->latestEmployee();
        $this->assertTrue($emp->all_branches);
        $this->assertCount(0, $emp->branches); // pivot stays empty; all_branches drives access
        foreach ([$this->damascus, $this->aleppo, $this->homs] as $b) {
            $this->assertTrue($emp->hasAbility('appointments.manage', $b));
        }
    }

    // ── Full Access + branch ──
    public function test_create_full_access_single_branch(): void
    {
        $this->store($this->payload(['full_access' => 1]))->assertRedirect();

        $emp = $this->latestEmployee();
        $this->assertTrue($emp->full_access);
        $this->assertFalse($emp->all_branches);
        $this->assertTrue($emp->hasAbility('cash.manage', $this->damascus));   // everything in Damascus
        $this->assertFalse($emp->hasAbility('cash.manage', $this->aleppo));    // but not elsewhere
    }

    // ── Advanced permission (L3) + remove a permission ──
    public function test_advanced_l3_override_removes_a_permission(): void
    {
        // reception has customers.manage by default; override it away.
        $this->store($this->payload(['overrides' => ['customers' => 'none']]))->assertRedirect();

        $emp = $this->latestEmployee();
        $this->assertFalse($emp->hasAbility('customers.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('customers.view', $this->damascus));
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus)); // untouched
        $this->assertDatabaseHas('employee_permission', [
            'employee_id'   => $emp->id,
            'permission_id' => Permission::where('slug', 'customers.manage')->value('id'),
            'effect'        => 'deny',
        ]);
    }

    // ── Branch-specific override (L4) — Ahmed scenario ──
    public function test_advanced_l4_branch_specific_override(): void
    {
        $this->store($this->payload([
            'branch_ids'       => [$this->damascus->id, $this->aleppo->id],
            'per_branch'       => 1,
            'branch_overrides' => [$this->aleppo->id => ['appointments' => 'view']],
        ]))->assertRedirect();

        $emp = $this->latestEmployee();
        // Damascus: full manage; Aleppo: view only
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->damascus));
        $this->assertFalse($emp->hasAbility('appointments.manage', $this->aleppo));
        $this->assertTrue($emp->hasAbility('appointments.view', $this->aleppo));
        $this->assertDatabaseHas('branch_employee_permission', [
            'employee_id' => $emp->id,
            'branch_id'   => $this->aleppo->id,
            'effect'      => 'deny',
        ]);
    }

    // ── Modify branches on update ──
    public function test_update_modifies_branch_access(): void
    {
        $this->store($this->payload())->assertRedirect();
        $emp = $this->latestEmployee();
        $this->assertEqualsCanonicalizing([$this->damascus->id], $emp->branches->pluck('id')->all());

        $this->put(route('company.employees.update', $emp), $this->payload([
            'branch_ids' => [$this->damascus->id, $this->aleppo->id],
        ]))->assertRedirect();

        $emp = $emp->fresh(['branches']);
        $this->assertEqualsCanonicalizing([$this->damascus->id, $this->aleppo->id], $emp->branches->pluck('id')->all());
        $this->assertTrue($emp->hasAbility('appointments.manage', $this->aleppo));
    }

    // ── Selected mode requires at least one branch ──
    public function test_selected_mode_requires_a_branch(): void
    {
        $this->store($this->payload(['access_mode' => 'selected', 'branch_ids' => []]))
            ->assertSessionHasErrors('branch_ids');
    }

    // ── Company isolation: cannot assign another company's branch ──
    public function test_foreign_branch_is_ignored_on_assignment(): void
    {
        $otherCompany = Company::factory()->create();
        $foreign = Branch::factory()->create(['company_id' => $otherCompany->id]);

        $this->store($this->payload(['branch_ids' => [$this->damascus->id, $foreign->id]]))->assertRedirect();

        $emp = $this->latestEmployee();
        $this->assertEqualsCanonicalizing([$this->damascus->id], $emp->branches->pluck('id')->all());
        $this->assertFalse($emp->canAccessBranch($foreign));
    }
}
