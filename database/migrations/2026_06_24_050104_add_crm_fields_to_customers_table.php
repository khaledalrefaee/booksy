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
            $table->date('date_of_birth')->nullable()->after('age');
            $table->string('source', 30)->nullable()->after('is_banned');
            $table->unsignedInteger('loyalty_points')->default(0)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'source', 'loyalty_points']);
        });
    }
};
