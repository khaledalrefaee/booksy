<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_leaves', function (Blueprint $table) {
            $table->string('type', 20)->default('annual')->after('end_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedSmallInteger('annual_leave_days')->default(21)->after('contract_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('employee_leaves', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('annual_leave_days');
        });
    }
};
