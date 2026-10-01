<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Message templates are owner-managed only: drop every company / branch text
 * and seed the platform's short default set (Arabic + English) for the owner
 * to review and edit. Existing system rows are kept as the owner left them.
 */
return new class extends Migration
{
    public function up(): void
    {
        SmsTemplate::whereNotNull('company_id')->orWhereNotNull('branch_id')->delete();

        foreach (['ar', 'en'] as $locale) {
            foreach (SmsTemplate::KEYS as $key) {
                SmsTemplate::firstOrCreate(
                    ['company_id' => null, 'branch_id' => null, 'key' => $key, 'locale' => $locale],
                    ['body' => SmsTemplate::defaultBody($key, $locale), 'is_active' => true]
                );
            }
        }
    }

    public function down(): void
    {
        // Deleted company texts cannot be restored.
    }
};
