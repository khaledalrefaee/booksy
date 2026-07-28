<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\StaffNotification;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class SendLicenseExpiryReminders extends Command
{
    protected $signature = 'employees:license-reminders';
    protected $description = 'Notify employees (WhatsApp) and companies (dashboard) about licenses expiring in 30/7/1 days';

    /** Reminder thresholds in days before expiry. */
    private const THRESHOLDS = [30, 7, 1];

    public function handle(WhatsappService $whatsapp): int
    {
        $sent = 0;

        foreach (self::THRESHOLDS as $days) {
            $employees = Employee::query()
                ->where('is_active', true)
                ->whereNotNull('license_expiry')
                ->whereDate('license_expiry', today()->addDays($days))
                ->get();

            foreach ($employees as $employee) {
                // WhatsApp to the employee (skipped silently when the service is offline)
                if ($whatsapp->sendLicenseExpiryReminder($employee, $days)) {
                    $sent++;
                }

                // Dashboard notification for the company
                StaffNotification::create([
                    'company_id' => $employee->company_id,
                    'branch_id'  => $employee->branch_id,
                    'type'       => 'license_expiry',
                    'title'      => __(':name license expires in :days day(s)', [
                        'name' => $employee->localizedName(),
                        'days' => $days,
                    ]),
                    'icon'  => '📜',
                    'color' => $days <= 7 ? '#ef4444' : '#f59e0b',
                    'link'  => route('company.employees.edit', $employee),
                ]);
            }
        }

        $this->info("License reminders processed — {$sent} WhatsApp message(s) sent.");

        return self::SUCCESS;
    }
}
