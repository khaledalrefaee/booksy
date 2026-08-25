<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Observers\AppointmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[ObservedBy(AppointmentObserver::class)]
class Appointment extends Model
{
    protected $fillable = [
        'booking_group_id',
        'reference',
        'idempotency_key',
        'company_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'employee_id',
        'employee_requested',
        'resource_id',
        'service_id',
        'start_time',
        'original_start_time',
        'reschedule_count',
        'rescheduled_at',
        'end_time',
        'status',
        'total_price',
        'tip_amount',
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

    public static function newGroupId(): string
    {
        return (string) Str::uuid();
    }

    /** Human-readable booking reference, shared by every row of one visit. */
    public static function newReference(): string
    {
        do {
            $ref = 'GR-' . strtoupper(Str::random(6));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    protected function casts(): array
    {
        return [
            'status'            => AppointmentStatus::class,
            'employee_requested'=> 'boolean',
            'start_time'        => 'datetime',
            'end_time'          => 'datetime',
            'total_price'       => 'decimal:2',
            'tip_amount'        => 'decimal:2',
            'original_start_time'=> 'datetime',
            'rescheduled_at'    => 'datetime',
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
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
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

    /** Full status history, oldest first. */
    public function transitions(): HasMany
    {
        return $this->hasMany(AppointmentTransition::class)->orderBy('created_at');
    }

    public function branchPayments(): HasMany
    {
        return $this->hasMany(BranchPayment::class);
    }

    public function appointmentServices(): HasMany
    {
        return $this->hasMany(AppointmentService::class)->orderBy('sort_order');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function groupAppointments(): HasMany
    {
        return $this->hasMany(static::class, 'booking_group_id', 'booking_group_id')
            ->where('id', '!=', $this->id);
    }

    public function displayName(): string
    {
        return $this->customer_name
            ?? $this->customer?->name
            ?? __('Guest');
    }
}
