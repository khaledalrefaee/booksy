<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    public $timestamps = false;

    protected $fillable = ['name_en', 'name_ar', 'code', 'dial_code', 'sort_order'];

    public function governorates(): HasMany
    {
        return $this->hasMany(Governorate::class)->orderBy('sort_order');
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? ($this->name_ar ?: $this->name_en) : ($this->name_en ?: $this->name_ar);
    }
}
