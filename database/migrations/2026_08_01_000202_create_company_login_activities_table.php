<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only log of business (company) login attempts, surfaced to the
     * platform owner as "Recent activity" + last-login per business.
     */
    public function up(): void
    {
        Schema::create('company_login_activities', function (Blueprint $table) {
            $table->id();
            // Nullable: failed logins may not resolve to a known company.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email_attempted')->nullable();
            $table->boolean('successful')->default(true);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['company_id', 'created_at']);
            $table->index(['successful', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_login_activities');
    }
};
