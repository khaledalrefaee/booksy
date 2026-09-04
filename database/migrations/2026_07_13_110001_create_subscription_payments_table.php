<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            // Financial history must survive company/plan/admin deletion:
            // FKs go null and the *_label snapshots keep the record readable.
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('company_label');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('plan_label')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 10);
            $table->string('method', 32)->default('cash');
            $table->string('reference')->nullable();
            $table->date('paid_at');

            $table->date('expires_before')->nullable();
            // Company's plan before this payment — needed to revert cleanly on void.
            $table->foreignId('plan_id_before')->nullable()->constrained('plans')->nullOnDelete();
            $table->date('expires_after')->nullable();
            $table->string('notes', 500)->nullable();

            // Financial rows are never hard-deleted — they get voided with a reason.
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('owners')->nullOnDelete();

            $table->timestamps();

            $table->index('paid_at');
            $table->index(['company_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
