<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_images', function (Blueprint $table) {
            $table->enum('type', ['place', 'work'])->default('place')->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('branch_images', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
