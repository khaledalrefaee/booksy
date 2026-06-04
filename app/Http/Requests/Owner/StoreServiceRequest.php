<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'service_category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
