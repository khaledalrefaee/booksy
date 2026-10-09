<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A unique visitor on a funnel page (welcome / join / business) — the denominator for conversion rate. */
class LeadVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visitor_id', 'page', 'source', 'campaign',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
        'landing_page', 'referral_host', 'device', 'locale', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
