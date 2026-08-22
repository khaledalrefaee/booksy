<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission vocabulary refactor: scope moves OUT of the slug and into the
 * branch dimension. `appointments.view_branch` / `view_company` become plain
 * `appointments.view` + `appointments.manage`, each tagged with a `level`.
 *
 * Safe to run: nothing enforces employee permissions yet (the company account
 * is the only actor today), so no runtime behaviour depends on the old slugs.
 * down() restores the original set + role mappings verbatim.
 */
return new class extends Migration
{
    /** New permissions: slug => [group, level]. */
    private array $permissions = [
        // ---- branch-level (gated by branch access) ----
        'appointments.view'      => ['appointments', 'branch'],
        'appointments.manage'    => ['appointments', 'branch'],
        'appointments.view_own'  => ['appointments', 'branch'], // special: provider sees only own
        'waitlist.view'          => ['waitlist', 'branch'],
        'waitlist.manage'        => ['waitlist', 'branch'],
        'customers.view'         => ['customers', 'branch'],
        'customers.manage'       => ['customers', 'branch'],
        'services.view'          => ['services', 'branch'],
        'services.manage'        => ['services', 'branch'],
        'inventory.view'         => ['inventory', 'branch'],
        'inventory.manage'       => ['inventory', 'branch'],
        'cash.view'              => ['finance', 'branch'],
        'cash.manage'            => ['finance', 'branch'],
        'attendance.view'        => ['attendance', 'branch'],
        'attendance.manage'      => ['attendance', 'branch'],
        'employees.view'         => ['employees', 'branch'],
        'employees.manage'       => ['employees', 'branch'],
        'reports.view'           => ['reports', 'branch'],
        'settings.branch'        => ['settings', 'branch'],
        // ---- company-level (skip branch gate; company-wide ability) ----
        'reports.view_company'   => ['reports', 'company'],
        'finance.view_company'   => ['finance', 'company'],
        'employees.admin'        => ['employees', 'company'],
        'settings.company'       => ['settings', 'company'],
        'billing.manage'         => ['billing', 'company'],
    ];

    /** Role slug => permission slugs. company_owner gets everything. */
    private function roleMap(array $allSlugs): array
    {
        return [
            'company_owner'  => $allSlugs,
            'branch_manager' => [
                'appointments.view', 'appointments.manage',
                'waitlist.view', 'waitlist.manage',
                'customers.view', 'customers.manage',
                'services.view', 'services.manage',
                'inventory.view', 'inventory.manage',
                'cash.view', 'cash.manage',
                'attendance.view', 'attendance.manage',
                'employees.view', 'employees.manage',
                'reports.view', 'settings.branch',
            ],
            'reception' => [
                'appointments.view', 'appointments.manage',
                'waitlist.view', 'waitlist.manage',
                'customers.view', 'customers.manage',
            ],
            'service_provider' => [
                'appointments.view_own', 'appointments.manage',
            ],
            'finance' => [
                'cash.view', 'cash.manage', 'finance.view_company',
                'reports.view', 'reports.view_company',
            ],
            'shop_staff' => [
                'services.view', 'services.manage',
                'inventory.view', 'inventory.manage',
            ],
        ];
    }

    public function up(): void
    {
        $this->replacePermissions(
            $this->permissions,
            fn (array $all) => $this->roleMap($all)
        );
    }

    public function down(): void
    {
        // Original set from 2026_05_07_002107 (no `level`; defaults to 'branch').
        $original = [
            'appointments.view_own'    => ['appointments', 'branch'],
            'appointments.view_branch' => ['appointments', 'branch'],
            'appointments.view_company'=> ['appointments', 'branch'],
            'appointments.manage_queue'=> ['appointments', 'branch'],
            'appointments.create_update'=> ['appointments', 'branch'],
            'waitlist.view_branch'     => ['waitlist', 'branch'],
            'waitlist.manage'          => ['waitlist', 'branch'],
            'finance.view_branch'      => ['finance', 'branch'],
            'finance.view_company'     => ['finance', 'branch'],
            'finance.record'           => ['finance', 'branch'],
            'products.view'            => ['products', 'branch'],
            'products.manage'          => ['products', 'branch'],
            'employees.manage_branch'  => ['employees', 'branch'],
            'branch.settings'          => ['settings', 'branch'],
            'company.settings'         => ['settings', 'branch'],
        ];

        $this->replacePermissions($original, function (array $all) {
            return [
                'company_owner'  => $all,
                'branch_manager' => [
                    'appointments.view_branch', 'appointments.view_company',
                    'appointments.manage_queue', 'appointments.create_update',
                    'waitlist.view_branch', 'waitlist.manage',
                    'finance.view_branch', 'finance.record',
                    'products.view', 'products.manage',
                    'employees.manage_branch', 'branch.settings',
                ],
                'reception' => [
                    'appointments.view_branch', 'appointments.manage_queue',
                    'appointments.create_update', 'waitlist.view_branch', 'waitlist.manage',
                ],
                'service_provider' => ['appointments.view_own', 'appointments.create_update'],
                'finance' => [
                    'finance.view_branch', 'finance.view_company',
                    'finance.record', 'appointments.view_company',
                ],
                'shop_staff' => ['products.view', 'products.manage'],
            ];
        });
    }

    /**
     * @param  array<string, array{0:string,1:string}>  $permissions
     * @param  callable(array<int,string>):array<string,array<int,string>>  $roleMapper
     */
    private function replacePermissions(array $permissions, callable $roleMapper): void
    {
        $now = now();

        DB::transaction(function () use ($permissions, $roleMapper, $now) {
            DB::table('permission_role')->delete();
            DB::table('permissions')->delete();

            foreach ($permissions as $slug => [$group, $level]) {
                DB::table('permissions')->insert([
                    'slug'       => $slug,
                    'group'      => $group,
                    'level'      => $level,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $roleIds = DB::table('roles')->pluck('id', 'slug')->all();
            $permIds = DB::table('permissions')->pluck('id', 'slug')->all();

            foreach ($roleMapper(array_keys($permissions)) as $roleSlug => $slugs) {
                if (! isset($roleIds[$roleSlug])) {
                    continue;
                }
                foreach ($slugs as $slug) {
                    if (! isset($permIds[$slug])) {
                        continue;
                    }
                    DB::table('permission_role')->insert([
                        'role_id'       => $roleIds[$roleSlug],
                        'permission_id' => $permIds[$slug],
                    ]);
                }
            }
        });
    }
};
