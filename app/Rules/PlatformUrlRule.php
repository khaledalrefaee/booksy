<?php

namespace App\Rules;

use App\Support\Platform\PlatformUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A link safe to store and print: http(s) only, no credentials, optional host allow-list / https-only / relative. */
class PlatformUrlRule implements ValidationRule
{
    public function __construct(private array $opts = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $reason = PlatformUrl::reason((string) $value, $this->opts);
        if ($reason === null) {
            return;
        }

        $fail(match ($reason) {
            'https' => __('This link must use https://.'),
            'host'  => __('This link must point to :hosts.', ['hosts' => implode(' / ', $this->opts['hosts'] ?? [])]),
            default => ! empty($this->opts['relative'])
                ? __('Enter a full link starting with https:// or a page path like /help.')
                : __('Enter a valid link that starts with https:// (or http://).'),
        });
    }
}
