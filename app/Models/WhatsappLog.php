<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    protected $fillable = [
        'company_id', 'appointment_id', 'phone', 'type',
        'message', 'status', 'error', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
}
