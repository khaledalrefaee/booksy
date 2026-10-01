<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'key', 'locale', 'body', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }

    /** The variables a template body may reference, for the editor's chips/help. */
    public const VARIABLES = [
        'customer_name',
        'branch_name',
        'appointment_date',
        'appointment_time',
        'service_name',
        'confirm_link',
        'cancel_link',
    ];

    /** The customer messages that have a template, in display order. */
    public const KEYS = ['confirmation', 'reminder', 'followup'];

    /**
     * The built-in texts: seeded as the owner's editable system templates and
     * the fallback if a row is ever missing. Short on purpose — Arabic is
     * Unicode (70 chars per SMS, 67 when it spans several), so every extra word
     * and every long link costs credits. The reminder carries its own confirm /
     * cancel links; the booking and follow-up messages carry none.
     */
    public static function defaultBody(string $key, string $locale = 'ar'): string
    {
        $ar = [
            'confirmation' => "تم حجز موعدك في {{branch_name}} 🎉\n{{appointment_date}} • {{appointment_time}}\nبانتظارك!",
            'reminder'     => "⏰ موعدك في {{branch_name}}\n{{appointment_date}} • {{appointment_time}}\nتأكيد: {{confirm_link}}\nإلغاء: {{cancel_link}}",
            'followup'     => "اشتقنا لك يا {{customer_name}} 💛\nاحجز موعدك القادم في {{branch_name}} متى شئت.",
        ];

        $en = [
            'confirmation' => "Your appointment at {{branch_name}} is booked for {{appointment_date}} at {{appointment_time}}. See you soon!",
            'reminder'     => "Reminder: your appointment at {{branch_name}} is {{appointment_date}} at {{appointment_time}}.\nConfirm: {{confirm_link}}\nCancel: {{cancel_link}}",
            'followup'     => "Hi {{customer_name}}, we miss you at {{branch_name}}! Book your next visit anytime.",
        ];

        $set = $locale === 'en' ? $en : $ar;

        return $set[$key] ?? '';
    }
}
