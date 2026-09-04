<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row platform settings for the SMS system — currently the price the
 * owner charges for one SMS credit and the display currency. Kept as its own
 * table (not env) so the owner can change pricing from the panel at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_per_sms', 12, 2)->default(0);
            $table->string('currency', 8)->default('SYP');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
