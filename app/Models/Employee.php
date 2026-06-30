<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Employee extends Model
{
    use HasLocalizedNames;

    public const CONTRACT_TYPES = [
        'full_time'  => ['label_key' => 'Full-time',  'icon' => '📋', 'color' => '#22c55e'],
        'part_time'  => ['label_key' => 'Part-time',  'icon' => '⏰', 'color' => '#f59e0b'],
        'temporary'  => ['label_key' => 'Temporary',  'icon' => '📌', 'color' => '#ef4444'],
        'freelance'  => ['label_key' => 'Freelance',  'icon' => '💼', 'color' => '#667eea'],
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
        return $this->hasMany(EmployeeWorkingHour::class)->orderBy('day_of_week');
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
}
