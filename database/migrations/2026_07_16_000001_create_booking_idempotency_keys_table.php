<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replay protection for booking writes.
     *
     * The appointments page queues writes locally when the connection drops and
     * replays them on reconnect. Without a server-side record of "this exact
     * request already happened", a replay — or a double-click, or a refresh
     * mid-request — silently creates a second appointment. The client sends a
     * key per booking attempt; the same key always returns the same response.
     */
    public function up(): void
    {
        Schema::create('booking_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            /** null = claimed but still processing; set = the response to replay */
            $table->json('response')->nullable();
            $table->timestamps();

            /* the claim itself: concurrent identical requests race on this */
            $table->unique(['company_id', 'key']);
            /* pruning old keys */
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_idempotency_keys');
    }
};
