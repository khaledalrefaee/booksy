<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday … 6=Saturday
            $table->boolean('is_working')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedTinyInteger('shift_number')->default(1);
            $table->timestamps();

            $table->unique(['employee_id', 'day_of_week', 'shift_number'], 'emp_wh_day_shift_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_working_hours');
    }
};
