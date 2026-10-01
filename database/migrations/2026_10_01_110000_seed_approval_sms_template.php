<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;

/** The owner-editable text sent when a pending booking is approved. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['ar', 'en'] as $locale) {
            SmsTemplate::firstOrCreate(
                ['company_id' => null, 'branch_id' => null, 'key' => 'approval', 'locale' => $locale],
                ['body' => SmsTemplate::defaultBody('approval', $locale), 'is_active' => true]
            );
        }
    }

    public function down(): void
    {
        SmsTemplate::whereNull('company_id')->where('key', 'approval')->delete();
    }
};
