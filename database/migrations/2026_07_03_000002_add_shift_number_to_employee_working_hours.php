<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_working_hours', 'shift_number')) {
            Schema::table('employee_working_hours', function (Blueprint $table) {
                $table->unsignedTinyInteger('shift_number')->default(1)->after('end_time');
            });
        }

        // Create the new unique first so the FK on employee_id keeps an index,
        // then drop the old unique.
        Schema::table('employee_working_hours', function (Blueprint $table) {
            $table->unique(['employee_id', 'day_of_week', 'shift_number'], 'emp_wh_day_shift_unique');
        });

        Schema::table('employee_working_hours', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_working_hours', function (Blueprint $table) {
            $table->unique(['employee_id', 'day_of_week']);
        });

        Schema::table('employee_working_hours', function (Blueprint $table) {
            $table->dropUnique('emp_wh_day_shift_unique');
            $table->dropColumn('shift_number');
        });
    }
};
