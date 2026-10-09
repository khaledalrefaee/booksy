<?php

namespace App\Models;

use App\Support\LeadCatalog;
use App\Support\LeadPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A business that raised its hand before launch. Created by the public /join
 * form (see App\Services\Leads\LeadSubmissionService), worked by the team in
 * /owner/leads. Never hard-deleted by the app: a repeat submission updates the
 * row and appends to the timeline instead.
 */
class Lead extends Model
{
    protected $fillable = [
        'full_name', 'phone', 'phone_normalized', 'whatsapp', 'email',
        'business_name', 'business_type', 'city', 'area', 'number_of_branches',
        'website', 'instagram', 'facebook', 'interests',
        'source', 'campaign', 'landing_page', 'referral',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
        'status', 'interaction_count', 'last_interaction_at', 'contacted_at', 'contacted_by',
        'locale', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'interests'           => 'array',
            'number_of_branches'  => 'integer',
            'interaction_count'   => 'integer',
            'last_interaction_at' => 'datetime',
            'contacted_at'        => 'datetime',
        ];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('created_at')->latest('id');
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'contacted_by');
    }

    /* ── Scopes ─────────────────────────────────────────────────────────── */

    /** Free-text search across the fields a salesperson actually remembers. */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        // A phone-looking query ("0933 111", "+963 933 111 222", "٠٩٣٣…") matches any part of the
        // canonical number, so formatting never decides whether a lead is found.
        $phoneish = preg_match('/^\+?[\d\s\-()٠-٩۰-۹]{3,}$/u', $term) === 1;
        $digits   = ltrim(LeadPhone::digits($term), '0');

        return $q->where(function (Builder $w) use ($like, $phoneish, $digits) {
            $w->where('full_name', 'like', $like)
              ->orWhere('business_name', 'like', $like)
              ->orWhere('email', 'like', $like)
              ->orWhere('phone', 'like', $like)
              ->orWhere('whatsapp', 'like', $like);

            if ($phoneish && strlen($digits) >= 3) {
                $w->orWhere('phone_normalized', 'like', '%'.$digits.'%');
            }
        });
    }

    /* ── Presentation helpers ───────────────────────────────────────────── */

    public function statusLabel(): string
    {
        return LeadCatalog::label('statuses', $this->status);
    }

    public function statusTone(): string
    {
        return LeadCatalog::tone($this->status);
    }

    public function businessTypeLabel(?string $locale = null): string
    {
        return LeadCatalog::label('business_types', $this->business_type, $locale);
    }

    public function cityLabel(?string $locale = null): string
    {
        return LeadCatalog::label('cities', $this->city, $locale);
    }

    public function sourceLabel(?string $locale = null): string
    {
        return LeadCatalog::label('sources', $this->source, $locale);
    }

    public function interestLabels(?string $locale = null): array
    {
        return LeadCatalog::interestLabels($this->interests, $locale);
    }

    /** wa.me link for the lead's WhatsApp number — null (button hidden) when missing/invalid. */
    public function whatsappUrl(?string $text = null): ?string
    {
        return LeadPhone::whatsappUrl($this->whatsapp, $text);
    }

    public function telUrl(): ?string
    {
        $n = LeadPhone::normalize($this->phone);

        return $n ? 'tel:+'.$n : null;
    }

    /** "@handle" or full URL ⇒ a clickable https link (null if empty). */
    public function instagramUrl(): ?string
    {
        return self::socialUrl($this->instagram, 'https://instagram.com/');
    }

    public function facebookUrl(): ?string
    {
        return self::socialUrl($this->facebook, 'https://facebook.com/');
    }

    public function websiteUrl(): ?string
    {
        return self::socialUrl($this->website, null);
    }

    public static function socialUrl(?string $value, ?string $handleBase): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
        }

        if ($handleBase !== null && preg_match('/^@?[A-Za-z0-9._]{1,60}$/', $value)) {
            return $handleBase.ltrim($value, '@');
        }

        $guess = 'https://'.ltrim($value, '/');

        return filter_var($guess, FILTER_VALIDATE_URL) && Str::contains($value, '.') ? $guess : null;
    }
}
