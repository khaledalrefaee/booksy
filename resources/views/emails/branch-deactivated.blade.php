<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Your branch has been deactivated') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f2ec;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2ec;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#5b6b3a;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:bold;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 12px;font-size:19px;color:#2b2b2b;">{{ __('Your branch has been deactivated') }}</h1>
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;">
                                {{ __('Hello :name,', ['name' => $company->owner_name ?: $company->localizedName()]) }}
                            </p>
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;">
                                {{ __('The branch ":branch" was set to inactive. It is now hidden from customers and no longer accepts bookings.', ['branch' => $branch->localizedName()]) }}
                            </p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;">
                                {{ __('You have been signed out for security. Sign in again anytime to reactivate it.') }}
                            </p>
                            <a href="{{ route('company.login') }}"
                               style="display:inline-block;background:#c9a227;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 26px;border-radius:24px;">
                                {{ __('Sign in') }}
                            </a>
                            <p style="margin:22px 0 0;font-size:12px;color:#8a8a8a;line-height:1.6;">
                                {{ __('If you did not do this, please sign in and review your account immediately.') }}
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:14px 0 0;font-size:11px;color:#9a9a9a;">© {{ date('Y') }} {{ config('app.name') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
