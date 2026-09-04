<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sellable SMS credit bundles the GlowRez owner defines (e.g. 200 / 500 /
 * 1000 / 5000). A company buys a package to top up a wallet; validity_days
 * (nullable) lets the owner expire the granted credits after a period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('credits');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 8)->default('SYP');
            // Null = credits never expire once granted.
            $table->unsignedSmallInteger('validity_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_packages');
    }
};
