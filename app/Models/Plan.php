<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasLocalizedNames;

    protected $fillable = [
        'name_en',
        'name_ar',
        'price',
        'currency',
        'duration_days',
        'grace_days',
        'features',
        'max_branches',
        'max_employees',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features'  => 'array',
            'price'     => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Every gateable module in the app. Anything not listed here
     * (dashboard, appointments, customers, branches, services, employees)
     * is core and always available.
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public static function featureCatalog(): array
    {
        return [
            'finance'         => ['label' => __('Finance (cash, invoices, expenses, debts)'), 'icon' => 'credit-card'],
            'inventory'       => ['label' => __('Inventory & products'),                      'icon' => 'package'],
            'payroll'         => ['label' => __('Payroll, deductions & advances'),            'icon' => 'dollar-sign'],
            'attendance'      => ['label' => __('Attendance'),                                'icon' => 'check-circle'],
            'leaves'          => ['label' => __('Leaves & team calendar'),                    'icon' => 'user-x'],
            'reports'         => ['label' => __('Reports & activity log'),                    'icon' => 'bar-chart-2'],
            'waitlist'        => ['label' => __('Waitlist'),                                  'icon' => 'clock'],
            'treatment_plans' => ['label' => __('Treatment plans'),                           'icon' => 'clipboard'],
            'loyalty'         => ['label' => __('Loyalty points'),                            'icon' => 'award'],
            'private_booking' => ['label' => __('Private booking page (QR)'),                 'icon' => 'lock'],
            'whatsapp'        => ['label' => __('WhatsApp notifications'),                    'icon' => 'message-circle'],
        ];
    }

    /** @return array<int, string> */
    public static function featureKeys(): array
    {
        return array_keys(self::featureCatalog());
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }
}
