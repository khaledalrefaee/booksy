<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_payment_id')->nullable()
                  ->constrained('branch_payments')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 5);
            $table->date('advance_date');
            $table->unsignedTinyInteger('installments_count')->default(1);
            $table->decimal('installment_amount', 14, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
        });

        // Installment deductions are linked to their advance and deleted with it
        DB::statement("ALTER TABLE employee_deductions MODIFY type ENUM('absence','tardiness','other','advance') NOT NULL DEFAULT 'absence'");
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->foreignId('advance_id')->nullable()->after('recorded_by_employee_id')
                  ->constrained('employee_advances')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advance_id');
        });
        DB::statement("ALTER TABLE employee_deductions MODIFY type ENUM('absence','tardiness','other') NOT NULL DEFAULT 'absence'");
        Schema::dropIfExists('employee_advances');
    }
};
