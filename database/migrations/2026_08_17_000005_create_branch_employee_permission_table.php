<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L4 — Per-branch employee permission overrides (OPTIONAL, advanced).
 * The most specific layer: a permission granted/revoked for one employee in
 * one branch. This is what powers "Ahmed manages in Damascus but only views in
 * Aleppo". Populated ONLY when the owner opens Advanced Permissions; stays empty
 * for the vast majority of businesses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_employee_permission', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->enum('effect', ['grant', 'deny']);
            $table->timestamps();

            $table->primary(['employee_id', 'branch_id', 'permission_id'], 'bep_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_employee_permission');
    }
};
