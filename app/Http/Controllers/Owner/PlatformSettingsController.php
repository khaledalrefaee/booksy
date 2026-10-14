<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\Platform\UpdatePlatformLinksRequest;
use App\Http\Requests\Owner\Platform\UpdatePlatformSettingsRequest;
use App\Services\Owner\OwnerAudit;
use App\Services\Platform\PlatformSettings;
use App\Support\Platform\PlatformIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Owner → Settings. Six tabs, one page: general · support · links · releases ·
 * visibility · system. Each tab saves on its own (PUT /owner/settings/{tab}).
 * Access: platform.settings.manage (route middleware); the maintenance switches
 * additionally need platform.maintenance.manage (enforced in the form request).
 */
class PlatformSettingsController extends Controller
{
    private const TABS = ['general', 'support', 'links', 'releases', 'visibility', 'system'];

    public function __construct(private PlatformSettings $settings) {}

    public function index(): View
    {
        $requested = request()->query('tab');
        $tab = session('tab') ?: (in_array($requested, self::TABS, true) ? $requested : 'general');

        return view('owner.settings.index', [
            'tab'            => $tab,
            'tabs'           => self::TABS,
            'v'              => $this->settings->all(),
            'links'          => $this->settings->links(),
            'logoUrl'        => $this->settings->logoUrl(),
            'runtime'        => PlatformSettings::runtime(),
            'maintenance'    => $this->settings->maintenance(),
            'canMaintenance' => (bool) Auth::guard('owner')->user()?->hasPermission('platform.maintenance.manage'),
            'presets'        => config('platform-settings.link_presets'),
            'customIcons'    => PlatformIcons::customKeys(),
            'weekdays'       => config('platform-settings.weekdays'),
            'maxLinks'       => (int) config('platform-settings.max_links', 30),
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request, string $tab): RedirectResponse
    {
        $validated = $request->validated();

        $values = [];
        foreach ($request->tabKeys() as $key) {
            if (Arr::has($validated, $key)) {
                $values[$key] = data_get($validated, $key);
            }
        }

        $newLogo = null;
        $oldLogo = $this->settings->string('general.logo');

        try {
            if ($tab === 'general') {
                if ($request->hasFile('logo')) {
                    $newLogo = $request->file('logo')->store('platform', 'public');
                    $values['general.logo'] = $newLogo;
                } elseif ($request->boolean('remove_logo')) {
                    $values['general.logo'] = null;
                }
            }

            $changes = $this->settings->save($values, Auth::guard('owner')->id());
        } catch (\Throwable $e) {
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }
            report($e);

            return back()->withInput($request->except('logo'))->with('tab', $tab)
                ->with('error', __('Could not save the settings. Please try again.'));
        }

        // The previous logo file is only deleted once the new state is safely stored.
        if (isset($changes['general.logo']) && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        if (! $changes) {
            return redirect()->route('owner.settings.index', ['tab' => $tab])->with('tab', $tab)
                ->with('info', __('Nothing was changed.'));
        }

        $this->audit($tab, $changes);

        return redirect()->route('owner.settings.index', ['tab' => $tab])->with('tab', $tab)
            ->with('success', __('Settings saved.'));
    }

    public function updateLinks(UpdatePlatformLinksRequest $request): RedirectResponse
    {
        $before = $this->settings->links()->map(fn ($l) => $l->only(['kind', 'label', 'url', 'is_active', 'show_public']))->all();

        try {
            $stats = $this->settings->syncLinks($request->rows());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('tab', 'links')
                ->with('error', __('Could not save the links. Please try again.'));
        }

        if (array_sum($stats) > 0) {
            $after = $this->settings->links()->map(fn ($l) => $l->only(['kind', 'label', 'url', 'is_active', 'show_public']))->all();
            OwnerAudit::record('platform-settings.links.updated', null, ['links' => $before], ['links' => $after] + ['summary' => $stats], null, 'Platform settings: links');
        }

        return redirect()->route('owner.settings.index', ['tab' => 'links'])->with('tab', 'links')
            ->with(array_sum($stats) > 0 ? 'success' : 'info', array_sum($stats) > 0 ? __('Links saved.') : __('Nothing was changed.'));
    }

    /** @param array<string,array{0:mixed,1:mixed}> $changes */
    private function audit(string $tab, array $changes): void
    {
        $old = $new = [];
        foreach ($changes as $key => [$from, $to]) {
            $old[$key] = $from;
            $new[$key] = $to;
        }

        $action = isset($changes['system.mode']) ? 'platform-settings.maintenance.changed' : "platform-settings.{$tab}.updated";

        OwnerAudit::record($action, null, $old, $new, null, 'Platform settings: '.$tab);
    }
}
