<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إغلاق الحساب الاختياري (Soft delete) للشركة — قابل للاستعادة من الأونر.
 * منفصل تماماً عن status='suspended' (عقوبة إدارية من المنصّة).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->softDeletes();                                  // deleted_at = تاريخ إغلاق الحساب
            $table->string('closure_reason', 500)->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('closure_reason');
            $table->dropSoftDeletes();
        });
    }
};
