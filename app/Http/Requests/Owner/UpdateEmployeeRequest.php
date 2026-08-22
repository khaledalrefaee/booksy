<?php

namespace App\Http\Requests\Owner;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')
                    ->where('company_id', $employee->company_id)
                    ->ignore($employee->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'bio' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'role_id' => ['required', 'exists:roles,id'],
            // Branch access (WHERE) + Full Access (WHAT) + optional overrides.
            'access_mode' => ['nullable', 'in:selected,all'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
            'full_access' => ['nullable', 'boolean'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', 'in:default,none,view,manage'],
            'per_branch' => ['nullable', 'boolean'],
            'branch_overrides' => ['nullable', 'array'],
            'branch_overrides.*' => ['nullable', 'array'],
            'branch_overrides.*.*' => ['nullable', 'in:default,none,view,manage'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('access_mode', 'selected') !== 'all' && empty($this->input('branch_ids'))) {
                $validator->errors()->add('branch_ids', __('Select at least one branch, or choose All branches.'));
            }
        });
    }
}
