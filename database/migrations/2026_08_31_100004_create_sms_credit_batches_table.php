<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A discrete grant of credits into a wallet (owner freebie, package purchase).
 * Consumption draws FIFO across a wallet's batches, decrementing `remaining`,
 * which is what lets credits carry an independent expiry (expires_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_credit_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('sms_wallets')->cascadeOnDelete();
            // grant | purchase
            $table->string('source', 16)->default('grant');
            $table->foreignId('package_id')->nullable()->constrained('sms_packages')->nullOnDelete();
            $table->unsignedInteger('credits');
            $table->unsignedInteger('remaining');
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('note')->nullable();
            // Owner who granted it (nullable = system / purchase).
            $table->foreignId('created_by_owner_id')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_batches');
    }
};
