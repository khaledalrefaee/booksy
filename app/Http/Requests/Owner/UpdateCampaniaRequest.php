<?php

namespace App\Http\Requests\Owner;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaniaRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($this->resolveCompanyId()),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    private function resolveCompanyId(): ?int
    {
        $company = $this->route('company') ?? $this->route('campania');

        if ($company instanceof Company) {
            return $company->getKey();
        }

        if (is_numeric($company)) {
            return (int) $company;
        }

        return null;
    }
}
