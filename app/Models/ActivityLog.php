<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'description', 'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'causer_name',
        'properties', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subjectLabel(): string
    {
        if (! $this->subject_type) return '—';
        return class_basename($this->subject_type) . ' #' . $this->subject_id;
    }
}
