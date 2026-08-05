<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-company onboarding state (one row per company). The checklist STEPS
     * themselves are derived from real data (has services / employees / working
     * hours / appointments) so they never drift; this table only persists the
     * cross-device UI state: guided-tour seen and checklist dismissed.
     */
    public function up(): void
    {
        Schema::create('company_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('tour_completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_onboarding');
    }
};
