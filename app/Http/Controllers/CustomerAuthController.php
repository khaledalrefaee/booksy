<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    /** Step 1: Send OTP */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|min:9|max:20']);

        $phone = preg_replace('/\s+/', '', $request->phone);

        // Rate limit: max 3 OTPs per 10 minutes
        $recent = OtpCode::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recent >= 3) {
            return response()->json(['message' => 'Too many attempts. Try again later.'], 429);
        }

        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(4),
        ]);

        // TODO: send real SMS. For now return code in dev mode.
        $isDev = app()->environment('local');

        return response()->json([
            'sent'    => true,
            'phone'   => $phone,
            'dev_code'=> $isDev ? $code : null, // shown in UI during development
        ]);
    }

    /** Step 2: Verify OTP */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string|size:4',
        ]);

        $phone = preg_replace('/\s+/', '', $request->phone);

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $otp->update(['used_at' => now()]);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name'  => '']   // empty until profile step
        );

        if (!$customer->phone_verified_at) {
            $customer->update(['phone_verified_at' => now()]);
        }

        session(['customer_id' => $customer->id]);

        return response()->json([
            'verified'        => true,
            'needs_profile'   => empty($customer->name),
            'customer'        => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
                'age'   => $customer->age,
            ],
        ]);
    }

    /** Step 3: Complete/update profile */
    public function saveProfile(Request $request): JsonResponse
    {
        $customer = $this->authCustomer();
        if (!$customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:80',
            'age'  => 'nullable|integer|min:10|max:100',
        ]);

        $customer->update([
            'name' => $request->name,
            'age'  => $request->age,
        ]);

        return response()->json(['saved' => true, 'customer' => [
            'id'    => $customer->id,
            'name'  => $customer->name,
            'phone' => $customer->phone,
            'age'   => $customer->age,
        ]]);
    }

    /** GET /customer/me */
    public function me(): JsonResponse
    {
        $customer = $this->authCustomer();
        if (!$customer) {
            return response()->json(['authenticated' => false]);
        }

        return response()->json([
            'authenticated' => true,
            'customer'      => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
                'age'   => $customer->age,
            ],
        ]);
    }

    /** POST /customer/logout */
    public function logout(): JsonResponse
    {
        session()->forget('customer_id');
        return response()->json(['logged_out' => true]);
    }

    /** Toggle favorite branch */
    public function toggleFavorite(Request $request): JsonResponse
    {
        $customer = $this->authCustomer();
        if (!$customer) {
            return response()->json(['message' => 'Login required.'], 401);
        }

        $request->validate(['branch_id' => 'required|exists:branches,id']);

        $exists = $customer->favoriteBranches()->where('branch_id', $request->branch_id)->exists();

        if ($exists) {
            $customer->favoriteBranches()->detach($request->branch_id);
            return response()->json(['favorited' => false]);
        } else {
            $customer->favoriteBranches()->attach($request->branch_id);
            return response()->json(['favorited' => true]);
        }
    }

    /** GET /customer/favorites */
    public function favorites(): JsonResponse
    {
        $customer = $this->authCustomer();
        if (!$customer) {
            return response()->json(['message' => 'Login required.'], 401);
        }

        $isAr     = app()->getLocale() === 'ar';
        $branches = $customer->favoriteBranches()->with(['company.category', 'images', 'reviews'])->get();

        $items = $branches->map(function ($b) use ($isAr) {
            $avg = $b->reviews->count() ? round($b->reviews->avg('rating'), 1) : null;
            $img = $b->images->first();
            return [
                'id'           => $b->id,
                'name'         => $isAr ? ($b->name_ar ?? $b->name_en) : ($b->name_en ?? $b->name_ar),
                'company_name' => $isAr ? ($b->company->name_ar ?? $b->company->name_en) : ($b->company->name_en ?? $b->company->name_ar),
                'image'        => $img ? asset('storage/' . $img->path) : null,
                'avg_rating'   => $avg,
                'review_count' => $b->reviews->count(),
                'url'          => route('front.branch', $b),
            ];
        });

        return response()->json(['favorites' => $items]);
    }

    public static function authCustomer(): ?Customer
    {
        $id = session('customer_id');
        return $id ? Customer::find($id) : null;
    }
}
