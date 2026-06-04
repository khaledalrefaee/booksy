<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocalizedNames
{
    /**
     * Label for admin UI matching the current locale, with sane fallbacks.
     */
    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $en = trim((string) ($this->name_en ?? ''));
        $ar = trim((string) ($this->name_ar ?? ''));

        if ($locale === 'ar') {
            return $ar !== '' ? $ar : $en;
        }

        return $en !== '' ? $en : $ar;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrderByLocalizedName(Builder $query, ?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();
        $primary = $locale === 'ar' ? 'name_ar' : 'name_en';
        $secondary = $locale === 'ar' ? 'name_en' : 'name_ar';

        return $query->orderBy($primary)->orderBy($secondary);
    }
}
