<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch-scoped permissions — L0 vocabulary tweak.
 *
 * `level` decides whether a permission is gated by the acting employee's branch
 * access (`branch`) or is a company-wide ability (`company`) that skips the
 * branch gate. Replaces the old scope-in-slug convention (view_branch/view_company).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('level', 16)->default('branch')->after('group');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
