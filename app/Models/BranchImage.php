<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchImage extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_BUSINESS = 'business';
    public const SOURCE_TEAM     = 'glowrez_team';

    public const TYPE_PLACE = 'place';
    public const TYPE_WORK  = 'work';

    protected $fillable = [
        'branch_id', 'path', 'type', 'sort_order',
        'status', 'source', 'is_cover', 'rejection_reason',
        'reviewed_at', 'reviewed_by', 'width', 'height', 'file_hash', 'flags',
    ];

    protected function casts(): array
    {
        return [
            'sort_order'  => 'integer',
            'is_cover'    => 'boolean',
            'width'       => 'integer',
            'height'      => 'integer',
            'flags'       => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /* ─────────────────────────  Scopes  ───────────────────────── */

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeRejected(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_REJECTED);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    /* ─────────────────────────  Helpers  ──────────────────────── */

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isTeam(): bool
    {
        return $this->source === self::SOURCE_TEAM;
    }

    /**
     * The business owner may freely manage (delete / replace / set-cover) their
     * own photos, but not team photos that have already been approved — those
     * they can only *request* changed or removed.
     */
    public function canBusinessManage(): bool
    {
        return ! ($this->isTeam() && $this->isApproved());
    }
}
