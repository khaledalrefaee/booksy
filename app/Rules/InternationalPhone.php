<?php

namespace App\Rules;

use App\Support\Platform\PlatformPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** International number with a country code, e.g. +963 944 123 456. */
class InternationalPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (PlatformPhone::normalize((string) $value) === null) {
            $fail(__('Enter the number with its country code, e.g. +963 944 123 456.'));
        }
    }
}
