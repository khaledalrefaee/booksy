<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchWorkingHour extends Model
{
    protected $fillable = [
        'branch_id',
        'day_of_week',
        'is_open',
        'open_time',
        'close_time',
        'shift_number',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_open' => 'boolean',
            'shift_number' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
