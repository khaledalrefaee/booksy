<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = OwnerNotification::query()
            ->with('company:id,name_en,name_ar')
            ->latest()
            ->paginate(30);

        return view('owner.notifications.index', compact('notifications'));
    }

    /** Mark one read and forward to its target (falls back to the list). */
    public function read(OwnerNotification $notification): RedirectResponse
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return redirect($notification->link ?? route('owner.notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        OwnerNotification::query()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
