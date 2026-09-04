<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsPackage extends Model
{
    protected $fillable = [
        'name', 'credits', 'price', 'currency',
        'validity_days', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'credits'       => 'integer',
            'price'         => 'decimal:2',
            'validity_days' => 'integer',
            'is_active'     => 'boolean',
            'sort_order'    => 'integer',
        ];
    }

    /** Unit price of a single SMS inside this package (0 when free). */
    public function pricePerSms(): float
    {
        return $this->credits > 0 ? round((float) $this->price / $this->credits, 4) : 0.0;
    }
}
