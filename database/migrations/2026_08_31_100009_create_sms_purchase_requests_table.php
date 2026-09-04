<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A company's request to buy an SMS package. There is no payment gateway or
 * live sending yet, so this simply records intent: the company asks, the owner
 * fulfils it by granting credits. status = pending | approved | rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('sms_packages')->nullOnDelete();
            $table->unsignedInteger('credits');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 8)->default('SYP');
            $table->string('status', 16)->default('pending');
            $table->string('note')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_purchase_requests');
    }
};
