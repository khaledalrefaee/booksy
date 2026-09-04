<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Derives a company's onboarding progress from real data (self-healing — never
 * drifts from what actually exists). The company_onboarding row only stores UI
 * state (tour seen / checklist dismissed), not the step completion itself.
 *
 * Ordered flow (see the product discussion): logo → location → working hours →
 * first service. Logo is a soft "quick win" first step; the other three are the
 * hard requirements that gate going live (`canPublish`). A company only appears
 * on the public marketplace once its status is `active`, which the publish
 * action flips — so an incomplete profile is never discoverable.
 */
class OnboardingService
{
    /**
     * Canonical setup steps in display order. `required` = gates publishing.
     * @return array<int, array{key:string, icon:string, required:bool}>
     */
    public static function stepDefinitions(): array
    {
        return [
            ['key' => 'logo',          'icon' => 'image',   'required' => false],
            ['key' => 'location',      'icon' => 'map-pin', 'required' => true],
            ['key' => 'working_hours', 'icon' => 'clock',   'required' => true],
            ['key' => 'service',       'icon' => 'scissors', 'required' => true],
        ];
    }

    /** @return array<string, bool> step key => completed */
    public static function completion(Company $company): array
    {
        $headOffice = $company->headOffice();
        $branchIds  = $company->branches()->pluck('id');

        return [
            'logo' => filled($company->logo),
            // Location is meaningful for discovery only when there is a map pin
            // AND a governorate (the coarse filter the marketplace uses).
            'location' => $headOffice !== null
                && $headOffice->governorate_id !== null
                && $headOffice->latitude !== null
                && $headOffice->longitude !== null,
            'working_hours' => $branchIds->isNotEmpty()
                && DB::table('branch_working_hours')->whereIn('branch_id', $branchIds)->exists(),
            'service' => $branchIds->isNotEmpty()
                && DB::table('services')->whereIn('branch_id', $branchIds)->exists(),
        ];
    }

    /** 0–100 integer percentage of setup completed (all steps, logo included). */
    public static function percent(Company $company): int
    {
        $done  = array_filter(self::completion($company));
        $total = count(self::stepDefinitions());

        return $total === 0 ? 100 : (int) round(count($done) / $total * 100);
    }

    public static function isComplete(Company $company): bool
    {
        return self::percent($company) === 100;
    }

    /** The required steps still missing — empty means the business can go live. */
    public static function publishBlockers(Company $company): array
    {
        $completion = self::completion($company);
        $blockers   = [];

        foreach (self::stepDefinitions() as $step) {
            if ($step['required'] && ! ($completion[$step['key']] ?? false)) {
                $blockers[] = $step['key'];
            }
        }

        return $blockers;
    }

    /** True when every *required* step is done (logo is optional). */
    public static function canPublish(Company $company): bool
    {
        return self::publishBlockers($company) === [];
    }

    /**
     * Everything a view needs: derived step completion + persisted UI state.
     * @return array{steps: array<string,bool>, percent: int, tourDone: bool,
     *   dismissed: bool, canPublish: bool, blockers: array<int,string>,
     *   published: bool, headOfficeId: int|null}
     */
    public static function summary(Company $company): array
    {
        $ob         = $company->onboarding;
        $headOffice = $company->headOffice();

        return [
            'steps'        => self::completion($company),
            'percent'      => self::percent($company),
            'tourDone'     => $ob?->tour_completed_at !== null,
            'dismissed'    => $ob?->dismissed_at !== null,
            'canPublish'   => self::canPublish($company),
            'blockers'     => self::publishBlockers($company),
            'published'    => $company->status === 'active',
            'headOfficeId' => $headOffice?->id,
        ];
    }
}
