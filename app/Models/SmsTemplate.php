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
    ];

    /** The built-in default bodies used to seed a company's templates. */
    public static function defaultBody(string $key, string $locale = 'ar'): string
    {
        $ar = [
            'confirmation' => "مرحباً {{customer_name}} 👋\nتم تأكيد حجزك في {{branch_name}} بتاريخ {{appointment_date}} الساعة {{appointment_time}}.\nنتطلّع لرؤيتك! 💛",
            'reminder'     => "تذكير: لديك موعد في {{branch_name}} بتاريخ {{appointment_date}} الساعة {{appointment_time}}.\nبانتظارك 💛",
            'followup'     => "مرحباً {{customer_name}}، اشتقنا لك في {{branch_name}}!\nمرّ وقت على آخر زيارة — احجز موعدك القادم في أي وقت 💛",
        ];

        $en = [
            'confirmation' => "Hi {{customer_name}} 👋\nYour appointment at {{branch_name}} on {{appointment_date}} at {{appointment_time}} is confirmed.\nSee you soon! 💛",
            'reminder'     => "Reminder: you have an appointment at {{branch_name}} on {{appointment_date}} at {{appointment_time}}.\nSee you there 💛",
            'followup'     => "Hi {{customer_name}}, we miss you at {{branch_name}}!\nIt's been a while since your last visit — book your next appointment anytime 💛",
        ];

        $set = $locale === 'en' ? $en : $ar;

        return $set[$key] ?? '';
    }
}
