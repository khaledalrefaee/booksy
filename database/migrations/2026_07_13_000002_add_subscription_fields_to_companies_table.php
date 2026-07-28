<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('category_id')
                ->constrained('plans')->nullOnDelete();
            $table->date('plan_expires_at')->nullable()->after('plan_id');
            $table->json('feature_overrides')->nullable()->after('plan_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['plan_expires_at', 'feature_overrides']);
        });
    }
};
