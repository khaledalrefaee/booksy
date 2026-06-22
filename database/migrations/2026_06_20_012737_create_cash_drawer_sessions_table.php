<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_drawer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->nullable();
            $table->decimal('expected_balance', 14, 2)->nullable();
            $table->decimal('variance', 14, 2)->nullable();
            $table->string('currency', 3)->default('SYP');
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('reconcile_reason', 50)->nullable();
            $table->text('reconcile_notes')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_drawer_sessions');
    }
};
