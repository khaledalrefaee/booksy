<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let the business panel add walk-in customers to the waitlist:
     * customer_id (users) becomes optional, name/phone stored directly.
     */
    public function up(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        DB::statement('ALTER TABLE waitlist_entries MODIFY customer_id BIGINT UNSIGNED NULL');

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone', 30)->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone']);
        });
    }
};
