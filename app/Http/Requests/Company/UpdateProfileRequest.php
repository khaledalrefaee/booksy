<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('company')->check();
    }

    public function rules(): array
    {
        $companyId = Auth::guard('company')->id();

        return [
            'name_en'  => ['required', 'string', 'max:255'],
            'name_ar'  => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($companyId)],
            'phone'    => ['nullable', 'string', 'max:30'],
            'logo'     => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'string', Password::min(8)->uncompromised(), 'confirmed'],
        ];
    }
}
