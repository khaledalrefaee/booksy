<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Employee extends Model
{
    use HasFactory;
    use HasLocalizedNames;

    public const CONTRACT_TYPES = [
        'full_time'  => ['label_key' => 'Full-time',  'icon' => '📋', 'color' => '#22c55e'],
        'part_time'  => ['label_key' => 'Part-time',  'icon' => '⏰', 'color' => '#f59e0b'],
        'temporary'  => ['label_key' => 'Temporary',  'icon' => '📌', 'color' => '#ef4444'],
        'freelance'  => ['label_key' => 'Freelance',  'icon' => '💼', 'color' => '#667eea'],
    ];

    public const TERMINATION_TYPES = [
        'resignation'  => ['label_key' => 'Resignation',        'icon' => '👋', 'color' => '#f59e0b'],
        'termination'  => ['label_key' => 'Termination',        'icon' => '🚫', 'color' => '#ef4444'],
        'contract_end' => ['label_key' => 'Contract ended',     'icon' => '📋', 'color' => '#94a3b8'],
        'retirement'   => ['label_key' => 'Retirement',         'icon' => '🌅', 'color' => '#667eea'],
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'role_id',
        'name_en',
        'name_ar',
        'phone',
        'email',
        'bio',
        'image',
        'is_active',
        'is_bookable',
        'password',
        'contract_type',
        'hire_date',
        'contract_end_date',
        'termination_date',
        'termination_type',
        'termination_reason',
        'annual_leave_days',
        'national_id',
        'iban',
        'bank_name',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'qualifications',
        'license_number',
        'license_expiry',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'is_bookable'       => 'boolean',
            'password'          => 'hashed',
            'hire_date'         => 'date',
            'contract_end_date' => 'date',
            'termination_date'  => 'date',
            'license_expiry'    => 'date',
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Service categories this employee is trained/assigned to */
    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceCategory::class,
            'employee_service_categories',
            'employee_id',
            'service_category_id'
        );
    }

    /** Individual services this employee can perform */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'employee_service')
                    ->withPivot('price', 'duration_minutes');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function handledAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'handled_by_employee_id');
    }

    public function preferredWaitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class, 'preferred_employee_id');
    }

    public function handledWaitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class, 'handled_by_employee_id');
    }

    public function recordedBranchPayments(): HasMany
    {
        return $this->hasMany(BranchPayment::class, 'recorded_by_employee_id');
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(EmployeeWorkingHour::class)->orderBy('day_of_week')->orderBy('shift_number');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(EmployeeLeave::class);
    }

    public function socialLinks(): MorphMany
    {
        return $this->morphMany(SocialLink::class, 'linkable');
    }

    public function compensation(): HasOne
    {
        return $this->hasOne(EmployeeCompensation::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function serviceCommissions(): HasMany
    {
        return $this->hasMany(EmployeeServiceCommission::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payrollPayments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    /**
     * The approved leave covering right now, if any. Full-day leaves cover their
     * whole date range; hourly permissions only cover their time window today.
     * Self-reverting: once the leave ends the employee reads as active again.
     */
    public function currentLeave(): ?EmployeeLeave
    {
        $now   = now();
        $today = $now->toDateString();

        return $this->leaves
            ->where('status', 'approved')
            ->first(function (EmployeeLeave $leave) use ($now, $today) {
                if ($today < $leave->start_date->toDateString() || $today > $leave->end_date->toDateString()) {
                    return false;
                }
                if ($leave->is_hourly) {
                    if (! $leave->start_hour || ! $leave->end_hour) {
                        return false;
                    }
                    $time = $now->format('H:i');
                    return $time >= substr($leave->start_hour, 0, 5) && $time < substr($leave->end_hour, 0, 5);
                }
                return true;
            });
    }

    public function isOnLeaveNow(): bool
    {
        return $this->currentLeave() !== null;
    }

    public function isTerminated(): bool
    {
        return $this->termination_date !== null;
    }

    /**
     * Day rate derived from the base salary (monthly ÷ 26, weekly ÷ 6, daily as-is).
     * Used for absence/tardiness deduction suggestions and unpaid-leave deductions.
     *
     * @return array{amount: float, currency: string}|null
     */
    public function dailyRate(): ?array
    {
        $comp = $this->compensation;
        if (! $comp || ! in_array($comp->type, ['salary', 'mixed']) || $comp->base_amount <= 0) {
            return null;
        }

        $amount = match ($comp->pay_period) {
            'daily'  => (float) $comp->base_amount,
            'weekly' => $comp->base_amount / 6,
            default  => $comp->base_amount / 26,
        };

        return [
            'amount'   => round($amount, 2),
            'currency' => $comp->currency ?? config('booksy.default_currency', 'SYP'),
        ];
    }

    public function terminationMeta(): ?array
    {
        return $this->termination_type ? (self::TERMINATION_TYPES[$this->termination_type] ?? null) : null;
    }

    /** Approved annual-leave days used within the given year (defaults to current year). Hourly permissions don't consume days. */
    public function annualLeaveUsed(?int $year = null): int
    {
        $year ??= now()->year;

        return $this->leaves
            ->where('status', 'approved')
            ->where('type', 'annual')
            ->where('is_hourly', false)
            ->sum(fn (EmployeeLeave $leave) => $leave->daysInYear($year));
    }

    /** Remaining annual-leave balance for the given year. Can go negative if over-used. */
    public function annualLeaveRemaining(?int $year = null): int
    {
        return (int) $this->annual_leave_days - $this->annualLeaveUsed($year);
    }
}
