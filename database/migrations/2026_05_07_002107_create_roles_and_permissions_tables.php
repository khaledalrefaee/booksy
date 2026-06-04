<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RBAC for staff: reception (queue/approve), provider (own calendar), finance (ledger), branch manager (branch-wide), company owner (all).
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('label_en', 128);
            $table->string('label_ar', 128)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('group', 48)->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        $now = now();

        $roles = [
            ['slug' => 'company_owner', 'label_en' => 'Company owner', 'label_ar' => 'مالك الشركة'],
            ['slug' => 'branch_manager', 'label_en' => 'Branch manager', 'label_ar' => 'مدير الفرع'],
            ['slug' => 'reception', 'label_en' => 'Reception', 'label_ar' => 'الاستقبال'],
            ['slug' => 'service_provider', 'label_en' => 'Service provider', 'label_ar' => 'مقدّم الخدمة'],
            ['slug' => 'finance', 'label_en' => 'Finance', 'label_ar' => 'المالية'],
            ['slug' => 'shop_staff', 'label_en' => 'Shop / catalog', 'label_ar' => 'المتجر / العرض'],
        ];

        foreach ($roles as $row) {
            DB::table('roles')->insert(array_merge($row, [
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $permissionRows = [
            ['slug' => 'appointments.view_own', 'group' => 'appointments'],
            ['slug' => 'appointments.view_branch', 'group' => 'appointments'],
            ['slug' => 'appointments.view_company', 'group' => 'appointments'],
            ['slug' => 'appointments.manage_queue', 'group' => 'appointments'],
            ['slug' => 'appointments.create_update', 'group' => 'appointments'],
            ['slug' => 'waitlist.view_branch', 'group' => 'waitlist'],
            ['slug' => 'waitlist.manage', 'group' => 'waitlist'],
            ['slug' => 'finance.view_branch', 'group' => 'finance'],
            ['slug' => 'finance.view_company', 'group' => 'finance'],
            ['slug' => 'finance.record', 'group' => 'finance'],
            ['slug' => 'products.view', 'group' => 'products'],
            ['slug' => 'products.manage', 'group' => 'products'],
            ['slug' => 'employees.manage_branch', 'group' => 'employees'],
            ['slug' => 'branch.settings', 'group' => 'settings'],
            ['slug' => 'company.settings', 'group' => 'settings'],
        ];

        foreach ($permissionRows as $row) {
            DB::table('permissions')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $roleIds = DB::table('roles')->pluck('id', 'slug')->all();
        $permIds = DB::table('permissions')->pluck('id', 'slug')->all();

        $attach = function (string $roleSlug, array $permissionSlugs) use ($roleIds, $permIds, $now): void {
            foreach ($permissionSlugs as $p) {
                if (! isset($roleIds[$roleSlug], $permIds[$p])) {
                    continue;
                }
                DB::table('permission_role')->insert([
                    'role_id' => $roleIds[$roleSlug],
                    'permission_id' => $permIds[$p],
                ]);
            }
        };

        $all = array_keys($permIds);

        $attach('company_owner', $all);

        $attach('branch_manager', [
            'appointments.view_branch',
            'appointments.view_company',
            'appointments.manage_queue',
            'appointments.create_update',
            'waitlist.view_branch',
            'waitlist.manage',
            'finance.view_branch',
            'finance.record',
            'products.view',
            'products.manage',
            'employees.manage_branch',
            'branch.settings',
        ]);

        $attach('reception', [
            'appointments.view_branch',
            'appointments.manage_queue',
            'appointments.create_update',
            'waitlist.view_branch',
            'waitlist.manage',
        ]);

        $attach('service_provider', [
            'appointments.view_own',
            'appointments.create_update',
        ]);

        $attach('finance', [
            'finance.view_branch',
            'finance.view_company',
            'finance.record',
            'appointments.view_company',
        ]);

        $attach('shop_staff', [
            'products.view',
            'products.manage',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
