<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('week_number')->nullable();
            $table->unsignedTinyInteger('day')->nullable();
            $table->string('pay_period', 10)->default('monthly');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('commissions', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency', 5)->default('SYP');
            $table->string('payment_method', 20)->default('cash');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year', 'week_number', 'day'], 'payroll_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
    }
};
