<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_head_office' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
            'landline_phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:4096'],
            'image_sort_orders' => ['nullable', 'array'],
            'image_sort_orders.*' => ['integer', 'min:0', 'max:65535'],
            'existing_sort_orders' => ['nullable', 'array'],
            'existing_sort_orders.*' => ['integer', 'min:0', 'max:65535'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer', 'exists:branch_images,id'],
        ];
    }
}
