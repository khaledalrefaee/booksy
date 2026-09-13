<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // SHA-256 hash of the mobile API bearer token. Nullable — a customer
            // only gets one once they verify their phone from the app. Never the
            // raw token; the plaintext is shown to the client exactly once.
            $table->string('api_token', 64)->nullable()->unique()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
