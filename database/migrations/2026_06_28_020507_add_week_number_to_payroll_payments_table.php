<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_payments', function (Blueprint $table) {
            $table->unsignedTinyInteger('week_number')->nullable()->after('year');
            $table->unsignedTinyInteger('day')->nullable()->after('week_number');
            $table->string('pay_period', 10)->default('monthly')->after('day');
        });

        // Drop the unique index (may need to drop FK first on MySQL)
        $fks = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_payments' AND COLUMN_NAME = 'employee_id' AND REFERENCED_TABLE_NAME IS NOT NULL"));

        Schema::table('payroll_payments', function (Blueprint $table) use ($fks) {
            foreach ($fks as $fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            }
            $table->dropUnique(['employee_id', 'month', 'year']);
        });

        Schema::table('payroll_payments', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unique(['employee_id', 'month', 'year', 'week_number', 'day'], 'payroll_unique_period');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_payments', function (Blueprint $table) {
            $table->dropUnique('payroll_unique_period');
            $table->dropForeign(['employee_id']);
            $table->unique(['employee_id', 'month', 'year']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->dropColumn(['week_number', 'day', 'pay_period']);
        });
    }
};
