<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store the most recent delivery failure reason so the owner can see WHY a
 * broadcast partly/fully failed (DNS, SMTP auth, rate limit, bad address …).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_emails', function (Blueprint $table) {
            $table->string('last_error', 500)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('owner_emails', function (Blueprint $table) {
            $table->dropColumn('last_error');
        });
    }
};
