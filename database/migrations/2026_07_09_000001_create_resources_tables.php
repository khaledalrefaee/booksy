<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('type')->default('room'); // room | equipment | other
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('resource_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unique(['resource_id', 'service_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('resource_id')->nullable()->after('employee_id')
                ->constrained('resources')->nullOnDelete();
            // Resource-conflict lookups filter by resource_id + start_time range.
            $table->index(['resource_id', 'start_time'], 'appts_resource_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resource_id');
        });
        Schema::dropIfExists('resource_service');
        Schema::dropIfExists('resources');
    }
};
