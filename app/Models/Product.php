<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasLocalizedNames;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name_en',
        'name_ar',
        'description',
        'price',
        'image',
        'sku',
        'sort_order',
        'is_active',
        'is_display_only',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_display_only' => 'boolean',
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
}
