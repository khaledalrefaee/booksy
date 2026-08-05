<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Full name of the account owner / manager (single field on purpose —
            // Arabic names don't split cleanly into first/last).
            $table->string('owner_name')->nullable()->after('name_ar');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('owner_name');
        });
    }
};
