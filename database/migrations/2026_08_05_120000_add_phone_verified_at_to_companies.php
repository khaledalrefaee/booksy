<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
        });

        // Existing companies predate the verification step — treat them as
        // already verified so they are never forced through the new flow.
        DB::table('companies')->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });
    }
};
