<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate the legacy single-branch model into the new layered one:
 *   - branch_id IS NULL  (legacy "all branches", was company_owner-only)  → all_branches = true
 *   - branch_id set                                                       → one branch_employee row
 *   - role = company_owner                                                → full_access = true
 *
 * Non-destructive: branch_id is kept as the home/primary branch.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ownerRoleId = DB::table('roles')->where('slug', 'company_owner')->value('id');
        $now = now();

        DB::table('employees')->orderBy('id')->chunkById(200, function ($employees) use ($ownerRoleId, $now) {
            foreach ($employees as $emp) {
                if ($emp->branch_id === null) {
                    DB::table('employees')->where('id', $emp->id)->update(['all_branches' => true]);
                } elseif (! DB::table('branch_employee')
                    ->where('branch_id', $emp->branch_id)
                    ->where('employee_id', $emp->id)
                    ->exists()) {
                    DB::table('branch_employee')->insert([
                        'branch_id'   => $emp->branch_id,
                        'employee_id' => $emp->id,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }

                if ($ownerRoleId && (int) $emp->role_id === (int) $ownerRoleId) {
                    DB::table('employees')->where('id', $emp->id)->update(['full_access' => true]);
                }
            }
        });
    }

    public function down(): void
    {
        // Flags live on employees (dropped by their own migration); pivot dropped by its own.
        DB::table('branch_employee')->truncate();
    }
};
