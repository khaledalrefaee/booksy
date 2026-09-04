<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'batch_id', 'sms_message_id', 'package_id',
        'type', 'credits', 'balance_after',
        'created_by', 'created_by_owner_id', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'credits'       => 'integer',
            'balance_after' => 'integer',
            'meta'          => 'array',
        ];
    }

    public function wallet(): BelongsTo  { return $this->belongsTo(SmsWallet::class, 'wallet_id'); }
    public function batch(): BelongsTo   { return $this->belongsTo(SmsCreditBatch::class, 'batch_id'); }
    public function message(): BelongsTo { return $this->belongsTo(SmsMessage::class, 'sms_message_id'); }
    public function package(): BelongsTo { return $this->belongsTo(SmsPackage::class); }

    /** A ledger row that added credits (grant/purchase/refund) vs. removed them. */
    public function isCredit(): bool
    {
        return $this->credits >= 0;
    }
}
