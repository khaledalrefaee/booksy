<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offboarding: resignation / termination record
        Schema::table('employees', function (Blueprint $table) {
            $table->date('termination_date')->nullable()->after('contract_end_date');
            $table->string('termination_type', 20)->nullable()->after('termination_date');
            $table->text('termination_reason')->nullable()->after('termination_type');
        });

        // Overtime & early-leave tracking per attendance record
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->integer('overtime_minutes')->default(0)->after('late_minutes');
            $table->integer('early_leave_minutes')->default(0)->after('overtime_minutes');
        });

        // Overtime hourly rate (in the salary currency)
        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->decimal('overtime_hourly_rate', 12, 2)->nullable()->after('product_commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['termination_date', 'termination_type', 'termination_reason']);
        });
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['overtime_minutes', 'early_leave_minutes']);
        });
        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->dropColumn('overtime_hourly_rate');
        });
    }
};
