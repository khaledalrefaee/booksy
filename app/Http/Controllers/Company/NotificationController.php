<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\StaffNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** Full notification history (read + unread) for the logged-in company. */
    public function index(): View
    {
        $companyId = Auth::guard('company')->id();

        $notifications = StaffNotification::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = StaffNotification::where('company_id', $companyId)
            ->unread()
            ->count();

        return view('company.notifications.index', compact('notifications', 'unreadCount'));
    }
}
