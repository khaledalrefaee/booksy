<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BookingWaitlistEntry;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer-facing online booking waitlist: join when a day is full, view/leave
 * from the account. The venue-side reception queue lives in Company\WaitlistController.
 */
class WaitlistController extends Controller
{
    /** POST /api/waitlist/join — "notify me when a slot opens" for a branch+day. */
    public function join(Request $request): JsonResponse
    {
        $customer = CustomerAuthController::authCustomer();
        if (! $customer) {
            return response()->json(['message' => 'Login required.'], 401);
        }

        $data = $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'date'         => 'required|date_format:Y-m-d',
            'service_ids'  => 'nullable|array',
            'service_ids.*'=> 'integer|exists:services,id',
            'pref_from'    => 'nullable|date_format:H:i',
            'pref_to'      => 'nullable|date_format:H:i',
        ]);

        if (\Illuminate\Support\Carbon::parse($data['date'])->endOfDay()->isPast()) {
            return response()->json(['message' => __('Pick a future day.'), 'past' => true], 422);
        }

        $branch     = Branch::findOrFail($data['branch_id']);
        $serviceIds = collect($data['service_ids'] ?? [])->filter()->unique()->values();
        if ($serviceIds->isEmpty()) {
            $serviceIds = collect([null]); // any service that day
        }

        $created = 0;
        foreach ($serviceIds as $sid) {
            // Don't stack duplicate waiting rows for the same wish.
            $exists = BookingWaitlistEntry::waiting()
                ->where('customer_id', $customer->id)
                ->where('branch_id', $branch->id)
                ->where('preferred_date', $data['date'])
                ->where('service_id', $sid)
                ->exists();
            if ($exists) continue;

            BookingWaitlistEntry::create([
                'customer_id'    => $customer->id,
                'company_id'     => $branch->company_id,
                'branch_id'      => $branch->id,
                'service_id'     => $sid,
                'preferred_date' => $data['date'],
                'pref_from'      => $data['pref_from'] ?? null,
                'pref_to'        => $data['pref_to'] ?? null,
                'status'         => 'waiting',
            ]);
            $created++;
        }

        $isAr = app()->getLocale() === 'ar';
        return response()->json([
            'joined'  => true,
            'created' => $created,
            'message' => $created > 0
                ? ($isAr ? 'سنُعلمك فور توفّر موعد في هذا اليوم 🔔' : 'We’ll notify you as soon as a slot opens that day 🔔')
                : ($isAr ? 'أنت مسجّل بالفعل في قائمة الانتظار لهذا اليوم.' : 'You’re already on the waitlist for that day.'),
        ]);
    }

    /** GET /account/waitlist — the customer's active waitlist entries. */
    public function index(Request $request)
    {
        $customer = app('current_customer');
        $isAr     = app()->getLocale() === 'ar';

        $entries = BookingWaitlistEntry::with(['branch', 'service'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->orderBy('preferred_date')
            ->get();

        return view('front.account.waitlist', compact('entries', 'isAr'));
    }

    /** DELETE /account/waitlist/{entry} — leave the waitlist. */
    public function destroy(Request $request, BookingWaitlistEntry $entry)
    {
        abort_unless($entry->customer_id === app('current_customer')->id, 404);
        $entry->delete();

        $isAr = app()->getLocale() === 'ar';
        return redirect()->route('account.waitlist')
            ->with('account_success', $isAr ? 'تمت إزالتك من قائمة الانتظار.' : 'You’ve been removed from the waitlist.');
    }
}
