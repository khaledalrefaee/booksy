<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyReward extends Model
{
    public const TYPE_FREE_SERVICE    = 'free_service';
    public const TYPE_PERCENT_ALL     = 'percent_all';
    public const TYPE_PERCENT_SERVICE = 'percent_service';

    protected $fillable = [
        'branch_id', 'name', 'type', 'service_id',
        'discount_percent', 'points_cost', 'is_active', 'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'type'      => self::TYPE_FREE_SERVICE,
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'points_cost'      => 'integer',
            'discount_percent' => 'integer',
            'sort_order'       => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** True when this reward grants a percentage discount rather than a free service. */
    public function isDiscount(): bool
    {
        return in_array($this->type, [self::TYPE_PERCENT_ALL, self::TYPE_PERCENT_SERVICE], true);
    }
}
