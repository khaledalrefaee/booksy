<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('branch_payments')->whereNull('payment_method')->update(['payment_method' => 'cash']);

        Schema::table('branch_payments', function (Blueprint $table) {
            $table->string('payment_method', 48)->default('cash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('branch_payments', function (Blueprint $table) {
            $table->string('payment_method', 48)->nullable()->default(null)->change();
        });
    }
};
