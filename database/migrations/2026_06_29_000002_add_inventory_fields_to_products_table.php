<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->after('branch_id')
                ->constrained('product_categories')->nullOnDelete();
            $table->string('barcode', 64)->nullable()->after('sku');
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
            $table->string('currency', 3)->default('SYP')->after('cost_price');
            $table->string('unit', 32)->default('piece')->after('currency');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('unit');
            $table->boolean('track_stock')->default(true)->after('is_display_only');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
            $table->dropColumn(['barcode', 'cost_price', 'currency', 'unit', 'low_stock_threshold', 'track_stock']);
        });
    }
};
