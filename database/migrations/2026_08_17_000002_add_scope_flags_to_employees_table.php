<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two independent access axes on the employee:
 *   - all_branches: role permissions apply on EVERY company branch (WHERE = all)
 *   - full_access:  employee holds ALL permissions (WHAT = everything)
 *
 * They are orthogonal: full_access + one branch = can do everything, in that
 * branch only. Neither implies the other. `branch_id` stays as the optional
 * home/primary branch; the real access set now lives in `branch_employee`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('all_branches')->default(false)->after('branch_id');
            $table->boolean('full_access')->default(false)->after('all_branches');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['all_branches', 'full_access']);
        });
    }
};
