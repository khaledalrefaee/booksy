<?php

namespace App\Models;

use App\Enums\WaitlistPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'service_id',
        'preferred_employee_id',
        'status',
        'priority',
        'estimated_minutes',
        'preferred_start',
        'expires_at',
        'notes',
        'appointment_id',
        'handled_by_employee_id',
    ];

    public function displayName(): string
    {
        return $this->customer_name
            ?? $this->customer?->name
            ?? __('Guest');
    }

    protected function casts(): array
    {
        return [
            'preferred_start'   => 'datetime',
            'expires_at'        => 'datetime',
            'priority'          => WaitlistPriority::class,
            'estimated_minutes' => 'integer',
        ];
    }

    /** How long this person has been in the queue. */
    public function waitedMinutes(): int
    {
        return (int) $this->created_at->diffInMinutes(now());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** CRM customer — repointed from `users` to `customers` in the 2026-07-19 migration. */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
