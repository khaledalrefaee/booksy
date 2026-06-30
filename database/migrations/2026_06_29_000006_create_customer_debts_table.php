<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('original_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 16)->default('unpaid'); // unpaid, partial, paid, waived
            $table->string('notes')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_debt_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 24)->default('cash');
            $table->string('notes')->nullable();
            $table->string('recorded_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_payments');
        Schema::dropIfExists('customer_debts');
    }
};
