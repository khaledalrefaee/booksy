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
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            // salary | commission | mixed
            $table->enum('type', ['salary', 'commission', 'mixed'])->default('salary');
            // Fixed salary amount (for type=salary or mixed)
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->enum('pay_period', ['daily', 'weekly', 'monthly'])->default('monthly');
            // Commission settings (for type=commission or mixed)
            // flat = same % on all services | per_service = different % per service
            $table->enum('commission_type', ['flat', 'per_service'])->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable(); // e.g. 50.00 = 50%
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
