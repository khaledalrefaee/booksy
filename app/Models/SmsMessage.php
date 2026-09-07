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
        // Rasel (POST /messages/send) delivery outcome — provider-side only.
        'request_id', 'usage_id', 'queue_id', 'resolved_provider',
        'sender_source', 'provider_status', 'estimated_cost', 'cost_currency',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'segments'       => 'integer',
            'credits_used'   => 'integer',
            'estimated_cost' => 'decimal:4',
            'sent_at'        => 'datetime',
        ];
    }

    /**
     * Map the GlowRez automation kind to a Rasel `messageType`:
     * confirmation/reminder are transactional (utility); follow-ups are
     * marketing. Ad-hoc manual sends stay free_text.
     */
    public function raselMessageType(): string
    {
        return match ($this->message_type) {
            'confirmation', 'reminder' => 'utility',
            'followup'                 => 'marketing',
            default                    => 'free_text',
        };
    }

    /** True when this row carries any Rasel provider-side detail to show. */
    public function hasProviderInfo(): bool
    {
        return (bool) ($this->request_id || $this->provider_message_id
            || $this->resolved_provider || $this->error_code);
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
