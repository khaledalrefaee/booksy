<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;

/**
 * First-run scaffolding for a freshly registered company.
 *
 * A new company has zero branches, which deadlocks the whole setup checklist
 * (services / working hours both need a branch_id). We seed a single
 * head-office branch as a *skeleton* — name + phone copied from the company —
 * kept `inactive` so it never shows up on the public marketplace until the
 * owner completes the location / hours / first service and hits "publish".
 */
class CompanySetupService
{
    /** Create the head-office branch if the company has none. Idempotent. */
    public static function ensureHeadOffice(Company $company): Branch
    {
        $existing = $company->branches()->orderBy('id')->first();
        if ($existing) {
            return $existing;
        }

        return $company->branches()->create([
            'name_en'        => $company->name_en,
            'name_ar'        => $company->name_ar,
            'phone'          => $company->phone,
            'is_head_office' => true,
            // Active by default. Public visibility is gated by the *company*
            // status (stays 'pending' until the owner publishes), not the branch.
            'status'         => 'active',
            'booking_mode'   => 'marketplace',
            'sort_order'     => 0,
        ]);
    }
}
