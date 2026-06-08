<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasLocalizedNames;

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
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
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
}
