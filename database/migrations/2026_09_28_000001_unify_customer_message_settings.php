<?php

use App\Models\BookingPolicy;
use App\Models\Branch;
use App\Models\Company;
use App\Models\SmsAutomationSetting;
use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One place for customer messages ("رسائل العملاء").
 *
 * Until now booking/reminder messages were configured twice: the Reminders
 * card of the booking policy (WhatsApp + un-metered SMS) and the SMS
 * automations page (credit-tracked SMS). sms_automation_settings becomes the
 * single per-branch source for BOTH channels, so:
 *
 *  - it gains `ask_confirmation` (confirm / cancel links in the messages),
 *    which used to be the policy's `require_confirmation`;
 *  - every branch without a row gets one carrying what its effective policy
 *    did before (on-booking message, the reminder — which really went out
 *    1 hour before — and ask-to-confirm), so nothing a salon relied on stops;
 *  - custom policy texts become company message templates.
 *
 * The old policy columns stay in the table (unused) — no destructive drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_automation_settings', function (Blueprint $table) {
            $table->boolean('ask_confirmation')->default(true)->after('reminder_offset_minutes');
        });

        Branch::query()->orderBy('id')->each(function (Branch $branch) {
            $company = Company::find($branch->company_id);
            if (! $company) {
                return;
            }
            $policy = $company->effectiveBookingPolicy($branch);

            $row = SmsAutomationSetting::where('company_id', $company->id)
                ->where('branch_id', $branch->id)->first();

            if ($row) {
                // Already configured on the SMS page — keep that, only carry
                // the ask-to-confirm choice over.
                $row->update(['ask_confirmation' => (bool) $policy->require_confirmation]);
                return;
            }

            SmsAutomationSetting::create([
                'company_id'              => $company->id,
                'branch_id'               => $branch->id,
                'confirmation_enabled'    => (bool) $policy->reminder_on_booking,
                'reminder_enabled'        => (bool) $policy->reminder_3h,
                'reminder_offset_minutes' => 60,
                'ask_confirmation'        => (bool) $policy->require_confirmation,
                'followup_enabled'        => false,
                'followup_days'           => 15,
            ]);
        });

        // Custom texts from the old policy → company templates ({name} → {{customer_name}}).
        $map = [
            '{name}'    => '{{customer_name}}',
            '{service}' => '{{service_name}}',
            '{branch}'  => '{{branch_name}}',
            '{date}'    => '{{appointment_date}}',
            '{time}'    => '{{appointment_time}}',
            '{link}'    => '{{confirm_link}}',
        ];
        BookingPolicy::whereNull('branch_id')->get()->each(function (BookingPolicy $p) use ($map) {
            foreach (['msg_confirm' => 'confirmation', 'msg_reminder_3h' => 'reminder'] as $col => $key) {
                $body = trim((string) $p->{$col});
                if ($body === '') {
                    continue;
                }
                SmsTemplate::firstOrCreate(
                    ['company_id' => $p->company_id, 'branch_id' => null, 'key' => $key, 'locale' => 'ar'],
                    ['body' => strtr($body, $map), 'is_active' => true]
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_automation_settings', function (Blueprint $table) {
            $table->dropColumn('ask_confirmation');
        });
    }
};
