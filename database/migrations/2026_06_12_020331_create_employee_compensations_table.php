<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('type', ['salary', 'commission', 'mixed'])->default('salary');
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('SYP');
            $table->enum('pay_period', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->enum('commission_type', ['flat', 'per_service'])->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
