<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money in/out per branch (and company roll-up). Finance role reads / records here.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('branch_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            /** income | expense | refund | adjustment */
            $table->string('type', 24);
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('SAR');
            $table->string('payment_method', 48)->nullable();
            $table->string('reference', 128)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_payments');
    }
};
