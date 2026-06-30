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
        Schema::create('treatment_plan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('session_number');
            $table->decimal('amount_due', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->char('currency', 3)->default('SYP');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->date('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_sessions');
    }
};
