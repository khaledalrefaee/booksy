<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical business categories (industry / business type).
 *
 * This is the single source of truth for the "Business type" shown at
 * company registration. It is intentionally SHALLOW and CURATED (MVP):
 * a flat list of real business types, not a deep taxonomy and not the
 * service catalog. Services are a separate concern (see ServiceCategory)
 * and are NOT modeled here — a business picks ONE type, then defines its
 * own services later. Room to grow later (parent_id / service taxonomy)
 * without reworking this list.
 *
 * Idempotent: safe to run repeatedly. It also cleans up legacy/junk rows
 * left by factories or older seeders, repointing any attached companies
 * to a canonical category first (companies.category_id cascades on delete).
 */
class CategorySeeder extends Seeder
{
    /** Canonical business types. `icon` = Feather icon name (for future cards). */
    private function categories(): array
    {
        return [
            ['slug' => 'salon',              'name_en' => 'Salon',               'name_ar' => 'صالون',            'icon' => 'scissors'],
            ['slug' => 'barbershop',         'name_en' => 'Barbershop',          'name_ar' => 'صالون حلاقة',       'icon' => 'scissors'],
            ['slug' => 'spa',                'name_en' => 'Spa',                 'name_ar' => 'سبا',              'icon' => 'droplet'],
            ['slug' => 'beauty-center',      'name_en' => 'Beauty Center',       'name_ar' => 'مركز تجميل',        'icon' => 'star'],
            ['slug' => 'nail-studio',        'name_en' => 'Nail Studio',         'name_ar' => 'استوديو أظافر',     'icon' => 'edit-2'],
            ['slug' => 'eyelash-brows',      'name_en' => 'Eyelash & Brows',     'name_ar' => 'رموش وحواجب',       'icon' => 'eye'],
            ['slug' => 'aesthetic-clinic',   'name_en' => 'Aesthetic Clinic',    'name_ar' => 'عيادة تجميل',       'icon' => 'smile'],
            ['slug' => 'dermatology-clinic', 'name_en' => 'Dermatology Clinic',  'name_ar' => 'عيادة جلدية',       'icon' => 'activity'],
            ['slug' => 'dental-clinic',      'name_en' => 'Dental Clinic',       'name_ar' => 'عيادة أسنان',       'icon' => 'plus-circle'],
            ['slug' => 'medical-center',     'name_en' => 'Medical Center',      'name_ar' => 'مركز طبي',          'icon' => 'plus-square'],
            ['slug' => 'wellness-center',    'name_en' => 'Wellness Center',     'name_ar' => 'مركز عافية',        'icon' => 'heart'],
            ['slug' => 'massage-center',     'name_en' => 'Massage Center',      'name_ar' => 'مركز مساج',         'icon' => 'feather'],
        ];
    }

    /** Legacy slugs from older seeders → canonical slug they map to. */
    private function legacyMap(): array
    {
        return [
            'salon-women' => 'salon',
            'salon-men'   => 'barbershop',
            'demo-salon'  => 'salon',
            'demo-spa'    => 'spa',
        ];
    }

    public function run(): void
    {
        $categories = $this->categories();

        // 1) Upsert canonical categories (idempotent).
        foreach ($categories as $i => $cat) {
            Category::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name_en'    => $cat['name_en'],
                    'name_ar'    => $cat['name_ar'],
                    'icon'       => $cat['icon'],
                    'sort_order' => $i + 1,
                ],
            );
        }

        $canonicalSlugs = array_column($categories, 'slug');
        $idBySlug       = Category::query()->pluck('id', 'slug');

        // 2) Repoint companies off legacy categories, then drop the legacy rows.
        foreach ($this->legacyMap() as $oldSlug => $newSlug) {
            $old = Category::query()->where('slug', $oldSlug)->first();
            if (! $old || ! isset($idBySlug[$newSlug])) {
                continue;
            }
            DB::table('companies')->where('category_id', $old->id)
                ->update(['category_id' => $idBySlug[$newSlug]]);
            $old->delete();
        }

        // 3) Remove leftover non-canonical rows (faker junk) that have no
        //    companies attached — safe against the cascade delete.
        $orphans = Category::query()
            ->whereNotIn('slug', $canonicalSlugs)
            ->whereDoesntHave('companies')
            ->get();
        foreach ($orphans as $orphan) {
            $orphan->delete();
        }

        // 4) Anything non-canonical still holding companies is unexpected —
        //    leave it (never cascade-delete real companies) and flag it.
        $stuck = Category::query()
            ->whereNotIn('slug', $canonicalSlugs)
            ->withCount('companies')
            ->get();
        foreach ($stuck as $s) {
            $this->command?->warn(
                "CategorySeeder: non-canonical category '{$s->slug}' still has {$s->companies_count} companies; left untouched."
            );
        }
    }
}
