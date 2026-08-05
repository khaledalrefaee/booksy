<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Derives a company's onboarding progress from real data (self-healing — never
 * drifts from what actually exists). The company_onboarding row only stores UI
 * state (tour seen / checklist dismissed), not the step completion itself.
 */
class OnboardingService
{
    /**
     * Canonical setup steps. `key` is stable; `route` is where the CTA links.
     * @return array<int, array{key:string, icon:string}>
     */
    public static function stepDefinitions(): array
    {
        return [
            ['key' => 'service',        'icon' => 'scissors'],
            ['key' => 'employee',       'icon' => 'users'],
            ['key' => 'working_hours',  'icon' => 'clock'],
            ['key' => 'appointment',    'icon' => 'calendar'],
        ];
    }

    /** @return array<string, bool> step key => completed */
    public static function completion(Company $company): array
    {
        $branchIds = $company->branches()->pluck('id');

        return [
            'service' => $branchIds->isNotEmpty()
                && DB::table('services')->whereIn('branch_id', $branchIds)->exists(),
            'employee' => $company->employees()->exists(),
            'working_hours' => $branchIds->isNotEmpty()
                && DB::table('branch_working_hours')->whereIn('branch_id', $branchIds)->exists(),
            'appointment' => $company->appointments()->exists(),
        ];
    }

    /** 0–100 integer percentage of setup completed. */
    public static function percent(Company $company): int
    {
        $done = array_filter(self::completion($company));
        $total = count(self::stepDefinitions());

        return $total === 0 ? 100 : (int) round(count($done) / $total * 100);
    }

    public static function isComplete(Company $company): bool
    {
        return self::percent($company) === 100;
    }

    /**
     * Everything a view needs: derived step completion + persisted UI state.
     * @return array{steps: array<string,bool>, percent: int, tourDone: bool, dismissed: bool}
     */
    public static function summary(Company $company): array
    {
        $ob = $company->onboarding;

        return [
            'steps'     => self::completion($company),
            'percent'   => self::percent($company),
            'tourDone'  => $ob?->tour_completed_at !== null,
            'dismissed' => $ob?->dismissed_at !== null,
        ];
    }
}
