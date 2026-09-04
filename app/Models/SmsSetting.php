<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $fillable = ['price_per_sms', 'currency'];

    protected function casts(): array
    {
        return ['price_per_sms' => 'decimal:2'];
    }

    /** The single settings row, seeded from config defaults on first access. */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'price_per_sms' => (float) config('booksy.sms.credits.default_price', 25),
            'currency'      => config('booksy.sms.credits.currency', 'SYP'),
        ]);
    }
}
