<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'employee_id',
        'service_id',
        'start_time',
        'end_time',
        'status',
        'total_price',
        'payment_status',
        'notes',
        'rejection_reason',
        'handled_by_employee_id',
        'handled_at',
        'status_changed_by_type',
        'status_changed_by_id',
        'status_changed_by_name',
        'status_changed_at',
        'status_previous',
    ];

    protected function casts(): array
    {
        return [
            'start_time'        => 'datetime',
            'end_time'          => 'datetime',
            'total_price'       => 'decimal:2',
            'handled_at'        => 'datetime',
            'status_changed_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'handled_by_employee_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function branchPayments(): HasMany
    {
        return $this->hasMany(BranchPayment::class);
    }
}
