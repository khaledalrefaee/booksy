<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'customer_id', 'appointment_id',
        'template_id', 'wallet_id', 'message_type', 'phone', 'body',
        'segments', 'credits_used', 'status', 'provider',
        'provider_message_id', 'failure_reason', 'sent_at', 'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'segments'     => 'integer',
            'credits_used' => 'integer',
            'sent_at'      => 'datetime',
        ];
    }

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo    { return $this->belongsTo(Customer::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function template(): BelongsTo    { return $this->belongsTo(SmsTemplate::class); }
    public function wallet(): BelongsTo      { return $this->belongsTo(SmsWallet::class, 'wallet_id'); }

    public function isSent(): bool   { return $this->status === 'sent'; }
    public function isFailed(): bool { return $this->status === 'failed'; }
}
