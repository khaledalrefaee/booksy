<?php

namespace App\Http\Requests\Owner;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeesRequest extends FormRequest
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
        /** @var Branch $branch */
        $branch = $this->route('branch');
        $companyId = (int) $branch->company_id;

        return [
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.name_en' => ['required', 'string', 'max:255'],
            'employees.*.name_ar' => ['required', 'string', 'max:255'],
            'employees.*.phone' => ['nullable', 'string', 'max:32'],
            'employees.*.email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->where('company_id', $companyId),
            ],
            'employees.*.password' => ['required', 'string', 'min:8', 'max:255'],
            'employees.*.bio' => ['nullable', 'string', 'max:10000'],
            'employees.*.image' => ['nullable', 'image', 'max:2048'],
            'employees.*.is_active' => ['sometimes', 'boolean'],
            'wizard' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $emails = collect($this->input('employees', []))
                ->pluck('email')
                ->filter()
                ->map(fn (string $email) => strtolower($email));

            if ($emails->count() !== $emails->unique()->count()) {
                $validator->errors()->add('employees', __('Each employee must have a unique email.'));
            }
        });
    }
}
