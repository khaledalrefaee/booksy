<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the owners table into the home for all GlowRez internal staff
 * (super_admin, admin, field_sales, sales_manager, marketing, support).
 *
 * Existing rows keep working: role stays as-is and every new column is
 * nullable / has a sensible default. Permission resolution stays config-first
 * (config/owner-permissions.php) but each employee may carry extra granted
 * keys in `permissions`, so access is per-employee and not hard-coded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            // Account lifecycle ---------------------------------------------
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('disabled_at')->nullable()->after('is_active');

            // Per-employee permission overrides (merged with the role's set).
            $table->json('permissions')->nullable()->after('disabled_at');

            // First-login forced password change (mirrors the staff guard).
            $table->boolean('must_change_password')->default(false)->after('password');

            // Who created this employee (audit / "created by"), nullable so the
            // original bootstrap super_admin has no parent.
            $table->unsignedBigInteger('created_by')->nullable()->after('must_change_password');

            // Activity signals for the "last seen" column.
            $table->timestamp('last_login_at')->nullable()->after('created_by');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');

            $table->index('role');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'is_active',
                'disabled_at',
                'permissions',
                'must_change_password',
                'created_by',
                'last_login_at',
                'last_activity_at',
            ]);
        });
    }
};
