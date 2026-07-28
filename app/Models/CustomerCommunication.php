<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCommunication extends Model
{
    public $timestamps = false;

    public const TYPES = [
        'whatsapp'     => ['label_key' => 'WhatsApp',     'icon' => '💬', 'color' => '#25D366'],
        'call'         => ['label_key' => 'Call',          'icon' => '📞', 'color' => '#667eea'],
        'sms'          => ['label_key' => 'SMS',           'icon' => '📱', 'color' => '#f59e0b'],
        'note'         => ['label_key' => 'Note',          'icon' => '📝', 'color' => '#64748b'],
        'booking'      => ['label_key' => 'Booking',       'icon' => '📅', 'color' => '#3b82f6'],
        'confirmation' => ['label_key' => 'Confirmation',  'icon' => '✅', 'color' => '#22c55e'],
        'cancellation' => ['label_key' => 'Cancellation',  'icon' => '❌', 'color' => '#ef4444'],
        'completion'   => ['label_key' => 'Completion',    'icon' => '🎉', 'color' => '#a855f7'],
    ];

    public const DIRECTIONS = [
        'outgoing' => ['label_key' => 'Outgoing', 'icon' => '↗️'],
        'incoming' => ['label_key' => 'Incoming', 'icon' => '↙️'],
    ];

    protected $fillable = [
        'customer_id', 'branch_id', 'type', 'direction', 'message', 'created_by_name', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
}
