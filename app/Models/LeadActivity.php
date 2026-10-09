<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of a lead's timeline. Append-only: there is no update or delete UI. */
class LeadActivity extends Model
{
    public $timestamps = false;

    public const CREATED        = 'created';
    public const RESUBMITTED    = 'resubmitted';
    public const REOPENED       = 'reopened';
    public const STATUS_CHANGED = 'status_changed';
    public const NOTE           = 'note';

    protected $fillable = ['lead_id', 'owner_id', 'type', 'body', 'meta', 'created_at'];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
