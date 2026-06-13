<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('type', ['absence', 'tardiness', 'other'])->default('absence');
            $table->boolean('is_sick_leave')->default(false); // sick leave — not deducted
            $table->date('deduction_date');
            $table->decimal('amount', 10, 2)->nullable();   // fixed money deduction
            $table->decimal('hours', 4, 2)->nullable();     // hours absent/late
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
