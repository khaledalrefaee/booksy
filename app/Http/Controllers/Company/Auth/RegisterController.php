<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
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
            'email'                 => ['required', 'email', 'unique:companies,email'],
            'phone'                 => ['required', 'string', 'max:30'],
            'category_id'           => ['required', 'exists:categories,id'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $company = Company::query()->create([
            'name_en'     => $data['name_en'],
            'name_ar'     => $data['name_ar'] ?? null,
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'category_id' => $data['category_id'],
            'password'    => Hash::make($data['password']),
            'status'      => 'pending',
        ]);

        Auth::guard('company')->login($company);
        $request->session()->regenerate();

        return redirect()->route('company.dashboard');
    }
}
