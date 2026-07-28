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
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedSmallInteger('loyalty_points_per_visit')->default(10)->after('overpayment_to');
            $table->unsignedSmallInteger('loyalty_points_per_extra_service')->default(5)->after('loyalty_points_per_visit');
            $table->unsignedInteger('loyalty_points_per_currency_unit')->default(10000)->after('loyalty_points_per_extra_service');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_per_visit', 'loyalty_points_per_extra_service', 'loyalty_points_per_currency_unit']);
        });
    }
};
