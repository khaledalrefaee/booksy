<?php

namespace App\Http\Middleware;

use App\Services\Platform\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform maintenance / paused mode, driven by Owner → Settings → System.
 *
 * Who is let through (enforced here, on the server — not by hiding buttons):
 *   • /owner/* — the admin console, including its login, is NEVER blocked, so the
 *     team can always sign in and switch maintenance off again;
 *   • any request carrying an authenticated owner session that holds
 *     `owner-dashboard.view` (staff can browse the public site while it is closed);
 *   • infrastructure + app bootstrap endpoints (/up, /csrf-token, /locale/*,
 *     /api/platform/*, broadcasting auth) so health checks work and the mobile
 *     apps can still read the status they must show.
 *
 * Scope "public" (default) closes the public website, customer area and the
 * customer/mobile API, leaving company dashboards running. Scope "all" also
 * closes the company area.
 *
 * This is independent of Laravel's `php artisan down` (a deployment tool that
 * stops everything at the web-server level); both can coexist.
 */
class PlatformMaintenance
{
    private const ALWAYS_OPEN = ['owner', 'owner/*', 'up', 'csrf-token', 'locale/*', 'api/platform/*', 'broadcasting/*'];

    public function __construct(private PlatformSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $m = $this->settings->maintenance();

        if (! $m['active'] || $request->is(...self::ALWAYS_OPEN)) {
            return $next($request);
        }

        if ($m['scope'] !== 'all' && $request->is('company', 'company/*', 'api/company', 'api/company/*')) {
            return $next($request);
        }

        if ($this->isAuthorizedStaff($request)) {
            return $next($request);
        }

        return $this->block($request, $m);
    }

    private function isAuthorizedStaff(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false; // stateless API calls carry no owner session
        }

        try {
            $owner = Auth::guard('owner')->user();

            return $owner !== null && $owner->canAccessOwnerDashboard();
        } catch (\Throwable) {
            return false;
        }
    }

    private function block(Request $request, array $m): Response
    {
        $headers = ['Retry-After' => '600'];

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'status'  => false,
                'message' => $m['message'] ?: ($m['mode'] === 'paused'
                    ? __('The service is temporarily paused.')
                    : __('The service is under maintenance. Please try again shortly.')),
                'data'    => ['maintenance' => ['mode' => $m['mode'], 'status_page_url' => $m['status_url']]],
            ], 503, $headers);
        }

        return response()->view('errors.503', ['maintenance' => $m], 503, $headers);
    }
}
