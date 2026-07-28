<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_leaves', function (Blueprint $table) {
            // Optional salary deduction attached to the leave; the actual
            // EmployeeDeduction row is created when the leave is approved.
            $table->decimal('deduction_amount', 12, 2)->nullable()->after('notes');
            $table->string('deduction_currency', 5)->nullable()->after('deduction_amount');
            $table->foreignId('deduction_id')->nullable()->after('deduction_currency')
                  ->constrained('employee_deductions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deduction_id');
            $table->dropColumn(['deduction_amount', 'deduction_currency']);
        });
    }
};
