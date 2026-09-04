<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPurchaseRequest extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'package_id',
        'credits', 'price', 'currency', 'status', 'note', 'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'credits'    => 'integer',
            'price'      => 'decimal:2',
            'handled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function package(): BelongsTo { return $this->belongsTo(SmsPackage::class); }
}
