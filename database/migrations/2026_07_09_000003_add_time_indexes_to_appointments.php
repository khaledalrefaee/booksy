<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Calendar, day-grid, slot and resource-conflict queries all filter by a
     * scope column + a start_time range; these composite indexes cover them.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['company_id', 'start_time'],  'appts_company_start_idx');
            $table->index(['employee_id', 'start_time'], 'appts_employee_start_idx');
            $table->index(['branch_id', 'start_time'],   'appts_branch_start_idx');
            $table->index(['resource_id', 'start_time'], 'appts_resource_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appts_company_start_idx');
            $table->dropIndex('appts_employee_start_idx');
            $table->dropIndex('appts_branch_start_idx');
            $table->dropIndex('appts_resource_start_idx');
        });
    }
};
