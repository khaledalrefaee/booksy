<?php

namespace App\Http\Requests\Owner\Platform;

use App\Rules\InternationalPhone;
use App\Rules\PlatformUrlRule;
use App\Services\Platform\PlatformSettings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validates ONE settings tab (the {tab} route segment) against the schema in
 * config/platform-settings.php. Only keys that belong to that tab are validated
 * and later saved, so a crafted request can never write another tab's settings.
 */
class UpdatePlatformSettingsRequest extends FormRequest
{
    private const VERSION = '/^\d{1,4}(\.\d{1,4}){1,3}(-[0-9A-Za-z.\-]{1,20})?$/';

    public function tab(): string
    {
        return (string) $this->route('tab');
    }

    /** @return array<int,string> schema keys this tab may write */
    public function tabKeys(): array
    {
        $keys = [];
        foreach (config('platform-settings.tabs.'.$this->tab(), []) as $group) {
            $keys = array_merge($keys, PlatformSettings::keysOf($group));
        }

        // Maintenance switches are guarded by their own permission.
        if (! $this->canManageMaintenance()) {
            $keys = array_values(array_diff($keys, config('platform-settings.maintenance_keys', [])));
        }

        return array_values(array_diff($keys, ['general.logo'])); // the logo has its own upload handling
    }

    private function canManageMaintenance(): bool
    {
        return (bool) Auth::guard('owner')->user()?->hasPermission('platform.maintenance.manage');
    }

    public function authorize(): bool
    {
        $owner = Auth::guard('owner')->user();

        if (! $owner || ! $owner->hasPermission('platform.settings.manage')) {
            return false;
        }
        if (! array_key_exists($this->tab(), config('platform-settings.tabs', []))) {
            return false;
        }

        // Someone without the maintenance permission has those inputs disabled in the UI;
        // if they show up in the request anyway it was crafted — refuse it outright.
        if (! $this->canManageMaintenance()) {
            foreach (config('platform-settings.maintenance_keys', []) as $key) {
                if ($this->has($key)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach ($this->tabKeys() as $key) {
            if ($this->has($key)) {
                data_set($input, $key, PlatformSettings::sanitize($key, $this->input($key)));
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->tabKeys() as $key) {
            $def = PlatformSettings::def($key);
            $max = $def['max'] ?? 255;

            $rules[$key] = match ($def['type']) {
                'string', 'text' => ['nullable', 'string', 'max:'.$max],
                'email'   => ['nullable', 'email:rfc', 'max:190'],
                'phone'   => ['nullable', new InternationalPhone],
                'url'     => ['nullable', 'max:'.$max, new PlatformUrlRule(array_intersect_key($def, array_flip(['relative', 'https', 'hosts'])))],
                'bool'    => ['sometimes', 'boolean'],
                'enum'    => [isset($def['default']) ? 'sometimes' : 'nullable', Rule::in($def['options'])],
                'date'    => ['nullable', 'date_format:Y-m-d'],
                'time'    => ['nullable', 'date_format:H:i'],
                'int'     => ['nullable', 'integer', 'min:'.($def['min'] ?? 0), 'max:'.($def['max'] ?? PHP_INT_MAX)],
                'version' => ['nullable', 'string', 'max:30', 'regex:'.self::VERSION],
                'days'    => ['nullable', 'array', 'max:7'],
                default   => ['nullable'],
            };

            if ($def['type'] === 'days') {
                $rules[$key.'.*'] = [Rule::in(config('platform-settings.weekdays'))];
            }
        }

        if ($this->tab() === 'general') {
            $rules['logo'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000'];
            $rules['remove_logo'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $out = [];
        foreach (array_keys(PlatformSettings::schema()) as $key) {
            $out[$key] = __(PlatformSettings::def($key)['label']);
        }

        return $out + ['logo' => __('Logo')];
    }

    public function messages(): array
    {
        return [
            'email'          => __('Enter a valid email address.'),
            'date_format'    => __('Enter a valid date.'),
            'regex'          => __('Use a version like 1.4.2 (numbers separated by dots).'),
            'logo.max'       => __('The logo must be 2 MB or smaller.'),
            'logo.mimes'     => __('The logo must be a PNG, JPG or WebP image.'),
            'logo.dimensions'=> __('The logo must be between 64 and 4000 pixels on each side.'),
            'logo.uploaded'  => __('The logo could not be uploaded. Try a smaller image.'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            match ($this->tab()) {
                'support'  => $this->checkSupport($v),
                'releases' => $this->checkReleases($v),
                default    => null,
            };
        });
    }

    private function checkSupport(Validator $v): void
    {
        $from = $this->input('support.hours_from');
        $to   = $this->input('support.hours_to');

        if (($from === null) !== ($to === null)) {
            $v->errors()->add($from === null ? 'support.hours_from' : 'support.hours_to', __('Set both the opening and closing time, or leave both empty.'));
        }
    }

    private function checkReleases(Validator $v): void
    {
        foreach (['web', 'android', 'ios'] as $p) {
            $version = $this->input("release.{$p}_version");
            $min     = $this->input("release.{$p}_min_version");

            if ($version && $min && version_compare($min, $version, '>')) {
                $v->errors()->add("release.{$p}_min_version", __('The minimum supported version cannot be higher than the current version.'));
            }
        }

        if ($this->input('release.android_status') === 'available') {
            if (! $this->input('release.android_version')) {
                $v->errors()->add('release.android_version', __('Enter the version before marking the app as available.'));
            }
            if (! $this->input('release.android_play_url') && ! $this->input('release.android_apk_url')) {
                $v->errors()->add('release.android_play_url', __('An available app needs a Google Play link or a direct APK link.'));
            }
        }

        if ($this->input('release.ios_status') === 'available') {
            if (! $this->input('release.ios_version')) {
                $v->errors()->add('release.ios_version', __('Enter the version before marking the app as available.'));
            }
            if (! $this->input('release.ios_store_url')) {
                $v->errors()->add('release.ios_store_url', __('An available app needs an App Store link.'));
            }
        }
    }
}
