<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasLocalizedNames;

    protected $fillable = [
        'slug',
        'sort_order',
        'name_en',
        'name_ar',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
