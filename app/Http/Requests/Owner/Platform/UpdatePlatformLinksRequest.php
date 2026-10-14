<?php

namespace App\Http\Requests\Owner\Platform;

use App\Rules\PlatformUrlRule;
use App\Services\Platform\PlatformSettings;
use App\Support\Platform\PlatformIcons;
use App\Support\Platform\PlatformUrl;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** The complete, ordered list of GlowRez social / custom links (the form always submits all rows). */
class UpdatePlatformLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) Auth::guard('owner')->user()?->hasPermission('platform.settings.manage');
    }

    protected function prepareForValidation(): void
    {
        $links = [];
        foreach ((array) $this->input('links', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $links[] = [
                'id'          => isset($row['id']) && ctype_digit((string) $row['id']) ? (int) $row['id'] : null,
                'kind'        => is_string($row['kind'] ?? null) ? trim($row['kind']) : null,
                'label'       => PlatformSettings::sanitize('general.name_ar', $row['label'] ?? null), // plain single-line text
                'url'         => isset($row['url']) && is_string($row['url']) ? trim($row['url']) : null,
                'icon'        => is_string($row['icon'] ?? null) ? trim($row['icon']) : null,
                'is_active'   => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'show_public' => filter_var($row['show_public'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $this->merge(['links' => $links]);
    }

    public function rules(): array
    {
        $kinds = array_merge(array_keys(config('platform-settings.link_presets')), ['custom']);

        return [
            'links'               => ['present', 'array', 'max:'.config('platform-settings.max_links', 30)],
            'links.*.id'          => ['nullable', 'integer', Rule::exists('platform_links', 'id')],
            'links.*.kind'        => ['required', Rule::in($kinds)],
            'links.*.label'       => ['nullable', 'string', 'min:2', 'max:60'],
            'links.*.url'         => ['required', 'max:500', new PlatformUrlRule(['https' => false])],
            'links.*.icon'        => ['nullable', Rule::in(PlatformIcons::customKeys())],
            'links.*.is_active'   => ['boolean'],
            'links.*.show_public' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'links.*.label' => __('Link name'),
            'links.*.url'   => __('Link'),
            'links.*.kind'  => __('Platform'),
            'links.*.icon'  => __('Icon'),
        ];
    }

    public function messages(): array
    {
        return [
            'links.*.url.required'   => __('Enter the link, or remove this row.'),
            'links.*.label.min'      => __('Give the link a name (at least 2 characters).'),
            'links.max'              => __('You can add up to :max links.', ['max' => config('platform-settings.max_links', 30)]),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $presets = config('platform-settings.link_presets');
            $seen    = [];

            foreach ($this->input('links', []) as $i => $row) {
                if ($row['kind'] === 'custom') {
                    if (empty($row['label'])) {
                        $v->errors()->add("links.$i.label", __('Give the link a name (at least 2 characters).'));
                    }

                    continue;
                }

                // One row per platform preset; its URL must really point at that platform.
                if (isset($seen[$row['kind']])) {
                    $v->errors()->add("links.$i.kind", __(':name is already in the list.', ['name' => $presets[$row['kind']]['label']]));
                }
                $seen[$row['kind']] = true;

                $hosts = $presets[$row['kind']]['hosts'] ?? [];
                if (PlatformUrl::reason($row['url'], ['hosts' => $hosts]) === 'host') {
                    $v->errors()->add("links.$i.url", __('This link must point to :hosts.', ['hosts' => implode(' / ', $hosts)]));
                }
            }
        });
    }

    /** Rows ready for PlatformSettings::syncLinks (presets get their canonical name). */
    public function rows(): array
    {
        $presets = config('platform-settings.link_presets');

        return array_map(function (array $row) use ($presets) {
            if ($row['kind'] !== 'custom') {
                $row['label'] = $presets[$row['kind']]['label'];
            }

            return $row;
        }, $this->validated()['links'] ?? []);
    }
}
