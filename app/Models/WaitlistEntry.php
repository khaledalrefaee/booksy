<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'service_id',
        'preferred_employee_id',
        'status',
        'preferred_start',
        'notes',
        'appointment_id',
        'handled_by_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'preferred_start' => 'datetime',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function preferredEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'preferred_employee_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'handled_by_employee_id');
    }
}
