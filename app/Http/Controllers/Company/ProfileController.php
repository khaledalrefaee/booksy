<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $company = Auth::guard('company')->user()->load('plan');

        return view('company.profile.show', [
            'company'        => $company,
            'featureCatalog' => \App\Models\Plan::featureCatalog(),
            'usage'          => [
                'branches'  => $company->branches()->count(),
                'employees' => $company->employees()->count(),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $company = Auth::guard('company')->user();
        $data    = $request->validated();

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $company->update($data);

        return back()->with('success', __('Profile updated successfully.'));
    }
}
