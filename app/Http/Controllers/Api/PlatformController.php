<?php

namespace App\Http\Controllers\Api;

use App\Services\Platform\PlatformSettings;
use App\Support\Platform\PlatformUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/platform/config — what the mobile apps (and any client) may know about
 * the platform: public identity, public contact/support channels, social links,
 * maintenance state and release info. It reuses the same "public*" accessors as
 * the website, so visibility switches apply identically, and it NEVER contains
 * the internal support fields, notes or any secret.
 *
 * Optional query: ?platform=android|ios|web &version=1.2.0 — when given, the
 * response includes `update` with `update_required` / `update_available` computed
 * from the recorded minimum / latest versions. (Those are the versions recorded
 * in the dashboard; the app decides what to do with them.)
 *
 * Stays reachable during maintenance (see PlatformMaintenance) so apps can show why.
 */
class PlatformController extends ApiController
{
    public function __construct(private PlatformSettings $ps) {}

    public function config(Request $request): JsonResponse
    {
        $platform = $request->query('platform');
        $version  = is_string($request->query('version')) ? $request->query('version') : null;

        $releases = [
            'web'     => $this->ps->releaseFor('web'),
            'android' => $this->ps->releaseFor('android'),
            'ios'     => $this->ps->releaseFor('ios'),
        ];

        $data = [
            'name'        => $this->ps->localized('general.name') ?: 'GlowRez',
            'description' => $this->ps->localized('general.description'),
            'website_url' => PlatformUrl::href($this->ps->string('general.website_url')),
            'logo_url'    => $this->ps->logoUrl(),
            'default_locale' => $this->ps->string('general.default_locale'),

            'contact' => $this->ps->officialContact() + [
                'support_channels' => array_values(array_map(
                    fn ($c) => ['key' => $c['key'], 'value' => $c['value'], 'url' => $c['href']],
                    $this->ps->supportChannels(),
                )),
                'support_hours'   => $this->ps->supportHoursLabel(),
                'support_message' => $this->ps->localized('support.default_message'),
            ],

            'social_links' => array_map(
                fn ($l) => ['kind' => $l['kind'], 'label' => $l['label'], 'url' => $l['url']],
                $this->ps->publicLinks(),
            ),

            'legal' => [
                'privacy_url' => PlatformUrl::href($this->ps->string('visibility.privacy_url')) ?: route('front.privacy'),
                'terms_url'   => PlatformUrl::href($this->ps->string('visibility.terms_url')) ?: route('front.terms'),
            ],

            'status' => [
                'mode'            => $this->ps->maintenance()['mode'],
                'message'         => $this->ps->maintenance()['message'],
                'status_page_url' => $this->ps->maintenance()['status_url'],
                'changelog_url'   => PlatformUrl::href($this->ps->string('system.changelog_url')),
            ],

            'releases' => $releases,
        ];

        if (in_array($platform, ['android', 'ios', 'web'], true) && $version !== null) {
            $data['update'] = $this->ps->releaseFor($platform, $version) + ['platform' => $platform];
        }

        return $this->success($data);
    }
}
