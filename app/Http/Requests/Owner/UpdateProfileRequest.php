<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('owner')->check();
    }

    public function rules(): array
    {
        $ownerId = Auth::guard('owner')->id();

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('owners', 'email')->ignore($ownerId)],
            'phone'    => ['nullable', 'string', 'max:30'],
            'avatar'   => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'string', Password::min(8)->uncompromised(), 'confirmed'],
        ];
    }
}
