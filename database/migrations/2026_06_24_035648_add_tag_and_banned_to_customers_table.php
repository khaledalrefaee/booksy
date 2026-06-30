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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('tag', 20)->nullable()->after('notes');
            $table->boolean('is_banned')->default(false)->after('tag');
            $table->string('ban_reason', 255)->nullable()->after('is_banned');
            $table->timestamp('banned_at')->nullable()->after('ban_reason');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tag', 'is_banned', 'ban_reason', 'banned_at']);
        });
    }
};
