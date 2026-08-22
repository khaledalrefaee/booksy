<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OtpCode;
use App\Services\WhatsappService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    /** Step 1: choose a channel and enter the account email/phone. */
    public function showForgot(): View
    {
        return view('company.auth.forgot');
    }

    /** Step 2: generate + deliver a reset code (WhatsApp or email). */
    public function sendCode(Request $request, WhatsappService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:whatsapp,email'],
            'phone'   => ['nullable', 'required_if:channel,whatsapp', 'string', 'max:20'],
            'email'   => ['nullable', 'required_if:channel,email', 'email'],
        ]);

        $channel = $data['channel'];

        if ($channel === 'email') {
            $identifier = $data['email'];
            $company    = Company::query()->where('email', $identifier)->first();
        } else {
            $identifier = preg_replace('/\s+/', '', $data['phone']);
            $company    = Company::query()->where('phone', $identifier)->first();
        }

        // Always advance to the reset step — never reveal whether the account
        // exists (prevents account enumeration). We only actually send a code
        // when a matching company is found.
        if ($company) {
            $recent = OtpCode::query()
                ->where('phone', $company->phone)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($recent < 3) {
                $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

                OtpCode::query()->create([
                    'phone'      => $company->phone,
                    'code'       => $code,
                    'expires_at' => now()->addMinutes(10),
                ]);

                $this->deliver($company, $code, $channel, $whatsapp);
            }

            session(['pw_reset_phone' => $company->phone]);
        } else {
            session(['pw_reset_phone' => null]);
        }

        session([
            'pw_reset_channel'    => $channel,
            'pw_reset_identifier' => $identifier,
        ]);

        return redirect()->route('company.password.reset');
    }

    /** Step 3: show the code + new-password form. */
    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('pw_reset_channel')) {
            return redirect()->route('company.password.forgot');
        }

        return view('company.auth.reset', [
            'identifier' => session('pw_reset_identifier'),
            'channel'    => session('pw_reset_channel'),
        ]);
    }

    /** Step 4: verify the code and set the new password. */
    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'     => ['required', 'string', 'size:4'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $phone = session('pw_reset_phone');

        $otp = $phone ? OtpCode::query()
            ->where('phone', $phone)
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first() : null;

        $company = $phone ? Company::query()->where('phone', $phone)->first() : null;

        if (! $otp || ! $company) {
            return back()->withErrors(['code' => __('The code is invalid or has expired.')]);
        }

        $otp->update(['used_at' => now()]);
        // The Company model casts `password` as `hashed`, so it is hashed on save.
        $company->update(['password' => $data['password']]);

        $request->session()->forget(['pw_reset_phone', 'pw_reset_channel', 'pw_reset_identifier']);

        return redirect()->route('company.login')
            ->with('status', __('Your password has been reset. You can now sign in.'));
    }

    /** Send the reset code over the chosen channel. Failures are non-fatal. */
    private function deliver(Company $company, string $code, string $channel, WhatsappService $whatsapp): void
    {
        $isAr  = app()->getLocale() === 'ar';
        $brand = 'GlowRez';

        if ($channel === 'email') {
            $subject = $isAr ? "رمز استعادة كلمة المرور — {$brand}" : "Password reset code — {$brand}";
            $body = $isAr
                ? "رمز استعادة كلمة المرور الخاص بك هو: {$code}\n\nالرمز صالح لمدة 10 دقائق. إن لم تطلب ذلك، تجاهل هذه الرسالة."
                : "Your password reset code is: {$code}\n\nThe code is valid for 10 minutes. If you didn't request this, please ignore this email.";

            try {
                Mail::raw($body, function ($m) use ($company, $subject) {
                    $m->to($company->email)->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::warning("Password reset email failed: {$e->getMessage()}");
            }

            return;
        }

        // Deliver to the phone, routed by country: Syrian numbers → local SMS
        // (paid, Rasel), everyone else → WhatsApp. companyId=null so the plan
        // gate never blocks an account-security message.
        $phoneChannel = $whatsapp->channelFor($company->phone);

        // Short, single-line body for the paid SMS segment; rich body for WhatsApp.
        $message = $phoneChannel === 'sms'
            ? ($isAr
                ? "{$brand}: رمز استعادة كلمة المرور هو {$code} (صالح 10 دقائق). لا تشاركه مع أحد."
                : "{$brand}: Your password reset code is {$code} (valid 10 min). Do not share it.")
            : ($isAr
                ? "🔐 *{$brand}*\n\n"
                    . "رمز استعادة كلمة المرور:\n\n"
                    . "*{$code}*\n\n"
                    . "⏱️ صالح لمدة 10 دقائق\n"
                    . "🔒 لا تُشارك هذا الرمز مع أي أحد"
                : "🔐 *{$brand}*\n\n"
                    . "Your password reset code:\n\n"
                    . "*{$code}*\n\n"
                    . "⏱️ Valid for 10 minutes\n"
                    . "🔒 Never share this code with anyone");

        $whatsapp->send($company->phone, $message, null, null, 'password_reset', $phoneChannel);
    }
}
