<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A platform-owner broadcast email (archive record). Sending itself is done
 * off-request by {@see \App\Jobs\SendOwnerBroadcastJob}, which updates the
 * status + delivery counters on this row.
 */
class OwnerEmail extends Model
{
    protected $fillable = [
        'owner_id',
        'from_address',
        'from_name',
        'subject',
        'body',
        'audience',
        'recipients',
        'recipients_count',
        'sent_count',
        'failed_count',
        'status',
        'last_error',
    ];

    protected $casts = [
        'recipients'       => 'array',
        'recipients_count' => 'integer',
        'sent_count'       => 'integer',
        'failed_count'     => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /** Human label for the chosen audience. */
    public function audienceLabel(): string
    {
        return [
            'all'      => __('All companies'),
            'active'   => __('Active companies only'),
            'selected' => __('Selected companies'),
            'manual'   => __('Manual addresses'),
        ][$this->audience] ?? $this->audience;
    }
}
