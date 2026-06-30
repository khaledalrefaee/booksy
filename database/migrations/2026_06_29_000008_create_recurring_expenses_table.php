<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 32)->default('other_expense');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SYP');
            $table->string('frequency', 16)->default('monthly'); // daily, weekly, monthly, yearly
            $table->date('next_due_date');
            $table->date('last_run_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
