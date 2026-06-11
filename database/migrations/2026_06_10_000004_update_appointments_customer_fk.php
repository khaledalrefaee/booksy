<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop any existing FK on customer_id (name may vary)
        $fks = \DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appointments'
              AND COLUMN_NAME = 'customer_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        foreach ($fks as $fk) {
            \DB::statement("ALTER TABLE appointments DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Clear test data (appointments have no real customers yet)
        \DB::table('appointments')->truncate();

        // Add FK pointing to customers
        \DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE');

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();
        });
    }
};
