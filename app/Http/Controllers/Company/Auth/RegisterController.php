<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Services\LoginActivityService;
use App\Services\OwnerNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegister(): View
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('company.auth.register', compact('categories'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en'               => ['required', 'string', 'max:255'],
            'name_ar'               => ['nullable', 'string', 'max:255'],
            'owner_name'            => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'unique:companies,email'],
            // Phone arrives in E.164 (e.g. +9639...) from intl-tel-input; the
            // real per-country validity check happens client-side. This is a
            // format guard, not a length-only rule.
            'phone'                 => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/'],
            'category_id'           => ['required', 'exists:categories,id'],
            'password'              => ['required', 'string', 'min:8'],
            'terms'                 => ['accepted'],
        ], [
            'phone.regex'    => __('Please enter a valid phone number.'),
            'terms.accepted' => __('You must agree to the Terms of Service and Privacy Policy.'),
        ]);

        $company = Company::query()->create([
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'owner_name'  => $data['owner_name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'category_id' => $data['category_id'],
            'password'    => Hash::make($data['password']),
            'status'      => 'pending',
        ]);

        // Notify the platform owner + record the auto-login that follows.
        OwnerNotificationService::businessRegistered($company);

        Auth::guard('company')->login($company);
        $request->session()->regenerate();
        LoginActivityService::record($request, true, $company->id, $company->email);

        return redirect()->route('company.dashboard');
    }
}
