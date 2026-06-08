<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.is_open' => ['sometimes', 'boolean'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'hours.*.shift2_enabled' => ['nullable', 'boolean'],
            'hours.*.shift2_open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.shift2_close_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
