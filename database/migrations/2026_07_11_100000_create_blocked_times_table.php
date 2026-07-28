<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blocked time (Fresha-style): a window during which no bookings can be
     * placed — for one employee, or the whole branch when employee_id is NULL.
     */
    public function up(): void
    {
        Schema::create('blocked_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('reason', 190)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'start_time']);
            $table->index(['branch_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_times');
    }
};
