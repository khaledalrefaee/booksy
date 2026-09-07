<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Employees are created with a manager-set password; they must set
            // their own on first login (staff portal, Phase 4 auth).
            $table->boolean('must_change_password')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
