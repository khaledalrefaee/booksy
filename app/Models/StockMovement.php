<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    public const TYPES = ['purchase', 'sale', 'transfer_in', 'transfer_out', 'adjustment', 'return'];

    protected $fillable = [
        'company_id', 'product_id', 'branch_id', 'type',
        'quantity', 'quantity_before', 'quantity_after',
        'unit_cost', 'currency', 'reference',
        'related_branch_id', 'notes', 'created_by_name', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function relatedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'related_branch_id');
    }
}
