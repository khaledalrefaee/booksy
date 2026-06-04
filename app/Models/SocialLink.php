<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SocialLink extends Model
{
    protected $fillable = [
        'linkable_type',
        'linkable_id',
        'platform',
        'url',
    ];

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
