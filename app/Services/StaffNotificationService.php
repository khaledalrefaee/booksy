<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SmsWallet;
use App\Models\StaffNotification;

class StaffNotificationService
{
    public static function appointmentBooked(Appointment $appointment): void
    {
        $customerName = $appointment->customer_name ?? $appointment->customer?->name ?? '—';
        $service      = $appointment->service?->name ?? '';
        $time         = $appointment->start_time->format('h:i A');
        $date         = $appointment->start_time->translatedFormat('d M');

        StaffNotification::create([
            'company_id' => $appointment->company_id,
            'branch_id'  => $appointment->branch_id,
            'type'       => 'appointment_booked',
            'title'      => __('New appointment'),
            'body'       => "{$customerName} — {$service} · {$date} {$time}",
            'icon'       => '📅',
            'color'      => '#22c55e',
            'link'       => route('company.appointments.show', $appointment->id),
            'data'       => ['appointment_id' => $appointment->id],
        ]);
    }

    public static function appointmentConfirmed(Appointment $appointment): void
    {
        $customerName = $appointment->customer_name ?? $appointment->customer?->name ?? '—';

        StaffNotification::create([
            'company_id' => $appointment->company_id,
            'branch_id'  => $appointment->branch_id,
            'type'       => 'appointment_confirmed',
            'title'      => __('Appointment confirmed'),
            'body'       => "{$customerName} — " . $appointment->start_time->translatedFormat('d M · h:i A'),
            'icon'       => '✅',
            'color'      => '#22c55e',
            'link'       => route('company.appointments.show', $appointment->id),
            'data'       => ['appointment_id' => $appointment->id],
        ]);
    }

    public static function appointmentCancelled(Appointment $appointment): void
    {
        $customerName = $appointment->customer_name ?? $appointment->customer?->name ?? '—';

        StaffNotification::create([
            'company_id' => $appointment->company_id,
            'branch_id'  => $appointment->branch_id,
            'type'       => 'appointment_cancelled',
            'title'      => __('Appointment cancelled'),
            'body'       => "{$customerName} — " . $appointment->start_time->translatedFormat('d M · h:i A'),
            'icon'       => '❌',
            'color'      => '#ef4444',
            'link'       => route('company.appointments.show', $appointment->id),
            'data'       => ['appointment_id' => $appointment->id],
        ]);
    }

    /** Link to the company SMS purchase page, tolerant of it not existing yet. */
    private static function smsLink(): string
    {
        return \Illuminate\Support\Facades\Route::has('company.sms.purchase')
            ? route('company.sms.purchase')
            : url('/company/sms');
    }

    /** SMS balance dropped to (or below) the branch's low-balance threshold. */
    public static function smsBalanceLow(SmsWallet $wallet): void
    {
        $scope = $wallet->branch?->localizedName() ?? __('All branches');

        StaffNotification::create([
            'company_id' => $wallet->company_id,
            'branch_id'  => $wallet->branch_id,
            'type'       => 'sms_balance_low',
            'title'      => __('SMS balance running low'),
            'body'       => "{$scope} — " . __(':n SMS remaining', ['n' => $wallet->remaining()]),
            'icon'       => '⚠️',
            'color'      => '#f59e0b',
            'link'       => self::smsLink(),
            'data'       => ['wallet_id' => $wallet->id, 'remaining' => $wallet->remaining()],
        ]);
    }

    /** SMS balance hit zero — sending is paused until the wallet is topped up. */
    public static function smsBalanceEmpty(SmsWallet $wallet): void
    {
        $scope = $wallet->branch?->localizedName() ?? __('All branches');

        StaffNotification::create([
            'company_id' => $wallet->company_id,
            'branch_id'  => $wallet->branch_id,
            'type'       => 'sms_balance_empty',
            'title'      => __('SMS balance depleted'),
            'body'       => "{$scope} — " . __('Purchase more SMS to keep automations running.'),
            'icon'       => '🚫',
            'color'      => '#ef4444',
            'link'       => self::smsLink(),
            'data'       => ['wallet_id' => $wallet->id],
        ]);
    }

    public static function customerConfirmedViaWhatsApp(Appointment $appointment): void
    {
        $customerName = $appointment->customer_name ?? $appointment->customer?->name ?? '—';

        StaffNotification::create([
            'company_id' => $appointment->company_id,
            'branch_id'  => $appointment->branch_id,
            'type'       => 'customer_confirmed',
            'title'      => __('Customer confirmed via WhatsApp'),
            'body'       => "{$customerName} — " . $appointment->start_time->translatedFormat('d M · h:i A'),
            'icon'       => '💬',
            'color'      => '#25D366',
            'link'       => route('company.appointments.show', $appointment->id),
            'data'       => ['appointment_id' => $appointment->id],
        ]);
    }

    public static function customerCancelledViaWhatsApp(Appointment $appointment): void
    {
        $customerName = $appointment->customer_name ?? $appointment->customer?->name ?? '—';

        StaffNotification::create([
            'company_id' => $appointment->company_id,
            'branch_id'  => $appointment->branch_id,
            'type'       => 'customer_cancelled',
            'title'      => __('Customer cancelled via WhatsApp'),
            'body'       => "{$customerName} — " . $appointment->start_time->translatedFormat('d M · h:i A'),
            'icon'       => '⚠️',
            'color'      => '#f59e0b',
            'link'       => route('company.appointments.show', $appointment->id),
            'data'       => ['appointment_id' => $appointment->id],
        ]);
    }
}
