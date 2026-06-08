<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('owner.profile.show', [
            'owner' => Auth::guard('owner')->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $owner = Auth::guard('owner')->user();
        $data  = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($owner->avatar) {
                Storage::disk('public')->delete($owner->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('owners/avatars', 'public');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $owner->update($data);

        return back()->with('success', __('Profile updated successfully.'));
    }
}
