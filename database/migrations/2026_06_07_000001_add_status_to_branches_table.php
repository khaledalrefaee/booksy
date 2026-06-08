<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // active = open & visible, inactive = hidden from public, maintenance = visible but can't book
            $table->enum('status', ['active', 'inactive', 'maintenance'])
                  ->default('active')
                  ->after('is_head_office');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
