<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_leaves', function (Blueprint $table) {
            $table->boolean('is_hourly')->default(false)->after('type');
            $table->time('start_hour')->nullable()->after('is_hourly');
            $table->time('end_hour')->nullable()->after('start_hour');
        });
    }

    public function down(): void
    {
        Schema::table('employee_leaves', function (Blueprint $table) {
            $table->dropColumn(['is_hourly', 'start_hour', 'end_hour']);
        });
    }
};
