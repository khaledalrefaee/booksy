<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform messages (account verification codes, password resets) belong to no
 * company yet should still appear in the owner's SMS log — so a row may have no
 * company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Left nullable: platform rows would violate NOT NULL.
    }
};
