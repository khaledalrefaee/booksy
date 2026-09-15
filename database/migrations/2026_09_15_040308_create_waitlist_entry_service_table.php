<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A waiting client can want several services in one visit — the same shape a
 * booking already supports. This pivot holds that set; the legacy single
 * `service_id` column on waitlist_entries stays as the primary/first service
 * so older rows and any code still reading it keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entry_service', function (Blueprint $table) {
            $table->foreignId('waitlist_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['waitlist_entry_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entry_service');
    }
};
