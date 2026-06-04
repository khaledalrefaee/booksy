<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Industry / business type for companies (not service catalog categories). */
class Category extends Model
{
    use HasLocalizedNames;

    protected $fillable = [
        'slug',
        'sort_order',
        'name_en',
        'name_ar',
        'image',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
