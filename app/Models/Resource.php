<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bookable physical resource (room, device, chair…) that services may
 * require. An appointment holding a resource blocks it for its whole window.
 */
class Resource extends Model
{
    use HasLocalizedNames;

    public const TYPES = ['room', 'equipment', 'other'];

    protected $fillable = [
        'branch_id',
        'name_en',
        'name_ar',
        'type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'resource_service');
    }

    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'resource_service_category');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'room'      => __('Room'),
            'equipment' => __('Equipment'),
            default     => __('Other'),
        };
    }
}
