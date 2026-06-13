<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeServiceCommission extends Pivot
{
    public $incrementing = false;
    public $timestamps   = false;

    protected $table    = 'employee_service_commissions';
    protected $fillable = ['employee_id', 'service_id', 'rate'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:2'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
