<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable SMS bodies with {{variables}}. Resolution is most-specific-wins:
 * branch template → company template → system default (company_id null).
 * key = confirmation | reminder | followup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            // Null company = platform-wide system default.
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key', 32);
            $table->string('locale', 5)->default('ar');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'key', 'locale'], 'sms_templates_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
