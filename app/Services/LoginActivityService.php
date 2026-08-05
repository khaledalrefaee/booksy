<?php

namespace App\Services;

use App\Models\CompanyLoginActivity;
use Illuminate\Http\Request;

/**
 * Records business (company) login attempts for the owner activity feed.
 */
class LoginActivityService
{
    public static function record(
        Request $request,
        bool $successful,
        ?int $companyId = null,
        ?string $emailAttempted = null,
    ): void {
        CompanyLoginActivity::create([
            'company_id'      => $companyId,
            'email_attempted' => $emailAttempted,
            'successful'      => $successful,
            'ip'             => $request->ip(),
            'user_agent'     => substr((string) $request->userAgent(), 0, 512),
        ]);
    }
}
