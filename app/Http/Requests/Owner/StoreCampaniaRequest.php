<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaniaRequest extends FormRequest
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
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:companies,email'],
            'phone' => ['required', 'string', 'max:30'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'status' => ['required', 'string', Rule::in(['pending', 'active', 'suspended'])],
            'logo' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
