<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L3 — Employee-level permission overrides (OPTIONAL).
 * Grants or revokes a single permission for one employee across ALL their
 * branches, on top of their role defaults. Empty for the common case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_permission', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->enum('effect', ['grant', 'deny']);
            $table->timestamps();

            $table->primary(['employee_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_permission');
    }
};
