<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('tip_amount', 12, 2)->default(0)->after('total_price');
        });

        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->decimal('product_commission_rate', 5, 2)->default(0)->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('tip_amount');
        });

        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->dropColumn('product_commission_rate');
        });
    }
};
