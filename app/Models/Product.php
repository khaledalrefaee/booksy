<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasLocalizedNames;

    public const UNITS = ['piece', 'box', 'bottle', 'tube', 'ml', 'g', 'kg', 'set'];

    protected $fillable = [
        'company_id', 'branch_id', 'product_category_id',
        'name_en', 'name_ar', 'description',
        'price', 'cost_price', 'currency', 'unit',
        'image',
        'sort_order', 'is_active', 'is_display_only',
        'track_stock', 'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_display_only' => 'boolean',
            'track_stock' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockInBranch(int $branchId): int
    {
        return (int) $this->branchStocks()->where('branch_id', $branchId)->value('quantity');
    }

    public function totalStock(): int
    {
        return (int) $this->branchStocks()->sum('quantity');
    }

    public function profit(): float
    {
        return round((float) $this->price - (float) $this->cost_price, 2);
    }
}
