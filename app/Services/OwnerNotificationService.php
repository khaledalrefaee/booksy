<?php

namespace App\Services;

use App\Models\Company;
use App\Models\OwnerNotification;

/**
 * Creates platform-owner notifications (the bell in /owner header).
 */
class OwnerNotificationService
{
    public static function businessRegistered(Company $company): void
    {
        OwnerNotification::create([
            'company_id' => $company->id,
            'type'       => 'business_registered',
            'title'      => __('New business account created'),
            'body'       => $company->localizedName() . ' · ' . ($company->owner_name ?? '—'),
            'icon'       => '🏢',
            'color'      => '#4B5D34',
            'link'       => route('owner.companies.show', $company->id),
            'data'       => ['company_id' => $company->id],
        ]);
    }
}
