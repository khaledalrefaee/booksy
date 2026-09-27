<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;
    use HasLocalizedNames;

    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (empty($branch->slug)) {
                $branch->slug = $branch->generateSlug();
            }
        });

        static::updating(function (Branch $branch) {
            if ($branch->isDirty('name_en') || empty($branch->slug)) {
                $branch->slug = $branch->generateSlug();
            }
        });
    }

    protected $fillable = [
        'company_id',
        'name_en',
        'name_ar',
        'sort_order',
        'is_head_office',
        'status',
        'booking_mode',
        'slug',
        'phone',
        'phones',
        'address',
        'description_en',
        'description_ar',
        'country_id',
        'governorate_id',
        'area_id',
        'latitude',
        'longitude',
        'landline_phone',
        'landlines',
        'qr_code',
        'overpayment_to',
        'loyalty_points_per_visit',
        'loyalty_points_per_extra_service',
        'loyalty_points_per_currency_unit',
        // Branch Settings (time & booking) — see App\Support\BranchSettings
        'timezone',
        'time_format',
        'appointment_interval',
        'min_booking_notice',
        'max_booking_days',
        'first_day_of_week',
    ];

    public function isMarketplace(): bool { return $this->booking_mode === 'marketplace'; }
    public function isPrivate(): bool     { return $this->booking_mode === 'private'; }

    public function scopeMarketplace($query)
    {
        // Public listings: marketplace booking mode AND an active branch only.
        return $query->where('booking_mode', '!=', 'private')
                     ->where('status', 'active');
    }

    /**
     * The public page binds {branch:slug}. Legacy links (and QR codes printed before
     * slugs) carry the numeric id, so fall back to the id when no slug matches.
     * Every other route keeps the default id binding.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug') {
            return static::where('slug', $value)->first()
                ?? (ctype_digit((string) $value) ? static::find((int) $value) : null);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function generateSlug(): string
    {
        $base = Str::slug($this->name_en ?: $this->name_ar ?: 'branch') ?: 'branch';
        // an all-digit slug would be indistinguishable from a legacy /branch/{id} link
        if (ctype_digit($base)) {
            $base = 'branch-' . $base;
        }
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function privateBookingUrl(): string
    {
        return url('/s/' . $this->slug);
    }

    /**
     * Shareable marketing tracking link for a given source code (IN|FB|WA|WEB).
     * A customer who books after arriving through it has that source recorded on
     * the appointment. Uses the branch slug — never the numeric id.
     */
    public function trackingUrl(string $code): string
    {
        return url('/branch/' . $this->slug . '/' . strtoupper($code));
    }

    // Convenience helpers
    public function isActive(): bool    { return $this->status === 'active'; }
    public function isInactive(): bool  { return $this->status === 'inactive'; }
    public function isMaintenance(): bool { return $this->status === 'maintenance'; }

    public function statusLabel(): string
    {
        return match($this->status) {
            'active'      => 'Active',
            'inactive'    => 'Inactive',
            'maintenance' => 'Maintenance',
            default       => 'Unknown',
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'active'      => 'success',
            'inactive'    => 'secondary',
            'maintenance' => 'warning',
            default       => 'secondary',
        };
    }

    protected function casts(): array
    {
        return [
            'sort_order'     => 'integer',
            'is_head_office' => 'boolean',
            'latitude'       => 'decimal:8',
            'longitude'      => 'decimal:8',
            'phones'         => 'array',
            'landlines'      => 'array',
            'appointment_interval'   => 'integer',
            'min_booking_notice'     => 'integer',
            'max_booking_days'       => 'integer',
            'first_day_of_week'      => 'integer',
        ];
    }

    // ── Branch clock ─────────────────────────────────────────────────────────
    //
    // Two kinds of datetime live in the database:
    //
    //  • INSTANTS (created_at, paid_at, logs…) — stored in the application
    //    timezone (config('app.timezone')). Convert with toLocal() to show them
    //    in the branch's time.
    //
    //  • APPOINTMENT WALL-CLOCK TIMES (appointments.start_time/end_time, working
    //    hours, leaves) — stored as the branch's own local time ("10:00 at the
    //    Dubai branch" is saved as 10:00). That is the standard for future
    //    appointments: if a country changes its DST rules, a booking made for
    //    10:00 must stay at 10:00, which a pre-converted UTC value would not.
    //
    // Carbon objects for wall-clock times carry the application timezone, so
    // "now" must be expressed the same way before comparing: localNow() is the
    // branch's current wall-clock time in that frame. Never compare appointment
    // times against plain now() — it is the *server's* wall clock.

    /** The branch's IANA timezone, falling back to the application timezone. */
    public function tz(): string
    {
        static $valid = [];

        $tz = $this->timezone ?: config('app.timezone');
        $valid[$tz] ??= in_array($tz, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL), true);

        return $valid[$tz] ? $tz : config('app.timezone');
    }

    /**
     * Branch ids grouped by timezone — lets platform-wide jobs (reminders,
     * no-show sweeps) evaluate each branch against its own clock with one
     * query per distinct zone (usually just one).
     *
     * @return array<string, array<int,int>>  tz => [branch ids]
     */
    public static function idsByTimezone(): array
    {
        return static::query()->get(['id', 'timezone'])
            ->groupBy(fn (Branch $b) => $b->tz())
            ->map(fn ($group) => $group->pluck('id')->all())
            ->all();
    }

    /** Wall-clock "now" in a timezone, in the frame appointment times are stored in. */
    public static function wallNowIn(string $tz): \Illuminate\Support\Carbon
    {
        return now($tz)->shiftTimezone(config('app.timezone'));
    }

    /** Current wall-clock time at the branch, comparable with appointment times. */
    public function localNow(): \Illuminate\Support\Carbon
    {
        return static::wallNowIn($this->tz());
    }

    /** Today's date at the branch (start of day, same frame as localNow()). */
    public function localToday(): \Illuminate\Support\Carbon
    {
        return $this->localNow()->startOfDay();
    }

    /** A stored INSTANT (e.g. created_at) as the branch's local time, for display. */
    public function toLocal(\DateTimeInterface $instant): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::instance($instant)->setTimezone($this->tz());
    }

    /** An appointment WALL-CLOCK time as a real instant (e.g. for "is it past yet?"). */
    public function wallToInstant(\DateTimeInterface $wall): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse($wall->format('Y-m-d H:i:s'), $this->tz());
    }

    public function uses24HourClock(): bool
    {
        return $this->time_format === '24h';
    }

    /** PHP format string for times at this branch: "H:i" or "g:i A". */
    public function timeFormat(): string
    {
        return $this->uses24HourClock() ? 'H:i' : 'g:i A';
    }

    /**
     * Format a time for display in this branch's clock style.
     * Accepts a Carbon/DateTime or a "H:i" / "H:i:s" string (working hours).
     */
    public function formatTime(\DateTimeInterface|string|null $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        $carbon = $time instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($time)
            : \Illuminate\Support\Carbon::createFromFormat(strlen($time) > 5 ? 'H:i:s' : 'H:i', $time);

        return $carbon->locale(app()->getLocale())->translatedFormat($this->timeFormat());
    }

    /** "UTC+03:00" for the branch right now (DST-aware). */
    public function utcOffsetLabel(): string
    {
        return \App\Support\BranchSettings::offsetLabel($this->tz());
    }

    /** Memo for bookingPolicy() — one lookup per branch instance per request. */
    private ?BookingPolicy $resolvedBookingPolicy = null;

    /**
     * The Booking & Cancellation Policy that applies to this branch (the
     * company-wide one, or the branch's own in per-branch mode). It owns the
     * customer-facing rules: online booking, same-day, cancel / reschedule and
     * the cancellation deadline. This model owns only the branch's clock and
     * booking window.
     */
    public function bookingPolicy(): BookingPolicy
    {
        return $this->resolvedBookingPolicy ??= ($this->company?->effectiveBookingPolicy($this)
            ?? new BookingPolicy(BookingPolicy::defaults()));
    }

    /**
     * Customer-facing booking rules, as one small array the public booking UI
     * and the booking API share.
     */
    public function bookingRules(): array
    {
        $today = $this->localToday();

        return [
            'online'        => (bool) $this->bookingPolicy()->allow_online_booking,
            'same_day'      => (bool) $this->bookingPolicy()->allow_same_day_booking,
            'interval'      => (int) ($this->appointment_interval ?: 15),
            'min_notice'    => (int) ($this->min_booking_notice ?? 0),
            'max_days'      => (int) ($this->max_booking_days ?: 365),
            'today'         => $today->toDateString(),
            'last_date'     => $today->copy()->addDays((int) ($this->max_booking_days ?: 365))->toDateString(),
            'time_format'   => $this->uses24HourClock() ? '24h' : '12h',
            'first_day'     => (int) ($this->first_day_of_week ?? 0),
        ];
    }

    /**
     * Status a new ONLINE booking starts in: Confirmed when the policy confirms
     * online bookings automatically, Pending when they wait for approval.
     *
     * No-show protection still applies on auto-confirm: a customer with
     * {offense_threshold} no-shows at this company in the last
     * {offense_window_days} days stays Pending when the policy asks for manual
     * approval of at-risk customers.
     */
    public function onlineBookingStatus(?Customer $customer): \App\Enums\AppointmentStatus
    {
        $policy = $this->bookingPolicy();

        if (! $policy->auto_confirm_online_bookings) {
            return \App\Enums\AppointmentStatus::Pending;
        }

        if ($customer && $policy->protection_enabled && $policy->action_manual_confirm
            && $this->isRepeatNoShow($customer, $policy)) {
            return \App\Enums\AppointmentStatus::Pending;
        }

        return \App\Enums\AppointmentStatus::Confirmed;
    }

    /** Has this customer reached the policy's no-show threshold at this company? */
    private function isRepeatNoShow(Customer $customer, BookingPolicy $policy): bool
    {
        $threshold = max(1, (int) $policy->offense_threshold);

        return Appointment::query()
            ->where('company_id', $this->company_id)
            ->where('customer_id', $customer->id)
            ->where('status', \App\Enums\AppointmentStatus::NoShow)
            ->where('start_time', '>=', $this->localNow()->subDays(max(1, (int) $policy->offense_window_days)))
            // A group visit is one visit, however many rows it has.
            ->selectRaw('COUNT(DISTINCT COALESCE(booking_group_id, id)) as visits')
            ->value('visits') >= $threshold;
    }

    /**
     * The earliest start a customer may book right now at this branch
     * (branch wall clock + minimum notice).
     */
    public function earliestBookableStart(): \Illuminate\Support\Carbon
    {
        return $this->localNow()->addMinutes((int) ($this->min_booking_notice ?? 0));
    }

    /**
     * Why a customer cannot book an appointment starting at $start (a
     * wall-clock time), or null when the booking rules allow it.
     *
     * @return 'online_disabled'|'past'|'same_day_disabled'|'too_soon'|'too_far'|null
     */
    public function bookingBlockReason(\DateTimeInterface $start): ?string
    {
        $start = \Illuminate\Support\Carbon::instance($start);
        $rules = $this->bookingRules();

        if (! $rules['online']) {
            return 'online_disabled';
        }
        if ($start->lte($this->localNow())) {
            return 'past';
        }
        if (! $rules['same_day'] && $start->toDateString() === $rules['today']) {
            return 'same_day_disabled';
        }
        if ($start->lt($this->earliestBookableStart())) {
            return 'too_soon';
        }
        if ($start->toDateString() > $rules['last_date']) {
            return 'too_far';
        }

        return null;
    }

    /** Memo for openWeekdays(). */
    private ?array $resolvedOpenWeekdays = null;

    /**
     * Weekdays (0=Sun … 6=Sat) on which at least one active staff member
     * works — their own schedule, or the branch hours when they have none for
     * that day (Employee::shiftsOn). Drives which days the booking calendar
     * offers at all. Empty = the branch hasn't set any hours yet.
     *
     * @return array<int, int>
     */
    public function openWeekdays(): array
    {
        if ($this->resolvedOpenWeekdays !== null) {
            return $this->resolvedOpenWeekdays;
        }

        $this->loadMissing('workingHours');
        $staff = $this->employees()->where('is_active', true)->with('workingHours')->get();

        $open = [];
        foreach (range(0, 6) as $dow) {
            foreach ($staff as $emp) {
                if ($emp->shiftsOn($dow, $this)->isNotEmpty()) {
                    $open[] = $dow;
                    break;
                }
            }
        }

        return $this->resolvedOpenWeekdays = $open;
    }

    /** Customer-facing message for a bookingBlockReason() code. */
    public function bookingBlockMessage(string $reason): string
    {
        $ar = app()->getLocale() === 'ar';

        return match ($reason) {
            'online_disabled'   => $ar ? 'الحجز الإلكتروني غير متاح في هذا الفرع حالياً. تواصل مع الفرع مباشرة.' : 'Online booking is currently unavailable at this branch. Please contact the branch directly.',
            'past'              => $ar ? 'هذا الوقت مضى بالفعل. الرجاء اختيار وقت لاحق.' : 'This time has already passed. Please pick a later slot.',
            'same_day_disabled' => $ar ? 'لا يقبل هذا الفرع الحجز في نفس اليوم. اختر يوماً آخر.' : 'This branch does not take same-day bookings. Please pick another day.',
            'too_soon'          => $ar
                ? 'يجب الحجز قبل ' . \App\Support\BranchSettings::durationLabel((int) $this->min_booking_notice) . ' على الأقل من الموعد.'
                : 'Bookings must be made at least ' . \App\Support\BranchSettings::durationLabel((int) $this->min_booking_notice) . ' in advance.',
            'too_far'           => $ar
                ? 'يمكن الحجز حتى ' . \App\Support\BranchSettings::daysLabel((int) $this->max_booking_days) . ' مقدماً فقط.'
                : 'Bookings can only be made up to ' . \App\Support\BranchSettings::daysLabel((int) $this->max_booking_days) . ' ahead.',
            'closed'            => $ar ? 'المكان مغلق في هذا اليوم. اختر يوماً آخر.' : 'The venue is closed on this day. Please pick another day.',
            'not_working'       => $ar ? 'هذا الموظف لا يعمل في هذا اليوم. اختر يوماً آخر.' : 'This professional isn’t working on this day. Please pick another day.',
            'no_hours'          => $ar ? 'لم يحدّد المكان أوقات العمل بعد، لذلك لا يمكن الحجز إلكترونياً حالياً. تواصل مع المكان مباشرة.' : 'This venue hasn’t set its opening hours yet, so online booking isn’t available. Please contact the venue directly.',
            'no_more_today'     => $ar ? 'انتهت الأوقات المتاحة لهذا اليوم. اختر يوماً آخر.' : 'There are no more times left today. Please pick another day.',
            'fully_booked'      => $ar ? 'كل الأوقات محجوزة في هذا اليوم.' : 'Every time on this day is booked.',
            default             => $ar ? 'تعذّر الحجز في هذا الوقت.' : 'This time cannot be booked.',
        };
    }

    /**
     * Whether a customer may still cancel / reschedule a booking starting at
     * $start (wall-clock). Both come from the Booking & Cancellation Policy and
     * share its cancellation deadline, measured on the branch's clock.
     */
    public function customerCanCancel(\DateTimeInterface $start): bool
    {
        return (bool) $this->bookingPolicy()->allow_customer_cancel && $this->beforeChangeDeadline($start);
    }

    public function customerCanReschedule(\DateTimeInterface $start): bool
    {
        return (bool) $this->bookingPolicy()->allow_customer_reschedule && $this->beforeChangeDeadline($start);
    }

    /** The last moment (wall-clock) a customer may cancel or move a booking. */
    public function changeDeadlineFor(\DateTimeInterface $start): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::instance($start)->subMinutes((int) $this->bookingPolicy()->cancellation_deadline_minutes);
    }

    private function beforeChangeDeadline(\DateTimeInterface $start): bool
    {
        return $this->localNow()->lt($this->changeDeadlineFor($start));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── SMS credit system ────────────────────────────────────────────────────

    /** This branch's own SMS wallet (may be null → it uses the company pool). */
    public function smsWallet(): HasOne
    {
        return $this->hasOne(SmsWallet::class);
    }

    public function smsAutomationSetting(): HasOne
    {
        return $this->hasOne(SmsAutomationSetting::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * This branch's own public contact channels, in display order — phones
     * first, then its social links (never the company's or another branch's).
     * Only channels with data are returned, so the page never renders an
     * empty button.
     *
     * @return array<int, array{kind:string, platform:?string, label:string, value:string, href:string}>
     */
    public function publicContacts(): array
    {
        $out = [];

        $phones = array_values(array_unique(array_filter(array_merge(
            [$this->phone], (array) ($this->phones ?? []),
            [$this->landline_phone], (array) ($this->landlines ?? []),
        ), fn ($p) => filled($p))));

        foreach ($phones as $p) {
            $out[] = [
                'kind'     => 'phone',
                'platform' => null,
                'label'    => __('Phone'),
                'value'    => $p,
                'href'     => 'tel:' . preg_replace('/[^\d+]/', '', $p),
            ];
        }

        $links = $this->relationLoaded('socialLinks') ? $this->socialLinks : $this->socialLinks()->get();
        $order = array_keys(SocialLink::$platforms);
        $links = $links->filter(fn ($l) => isset(SocialLink::$platforms[$l->platform]) && filled($l->url))
            ->sortBy(fn ($l) => array_search($l->platform, $order, true));

        foreach ($links as $link) {
            $meta   = SocialLink::$platforms[$link->platform];
            $handle = SocialLink::extractHandle($link->platform, $link->url);
            $digits = $meta['input_type'] === 'phone' ? SocialLink::internationalDigits($handle, $this->country?->dial_code) : null;

            $value = match ($meta['input_type']) {
                'phone'  => '+' . $digits,
                'handle' => in_array($link->platform, ['instagram', 'twitter', 'tiktok', 'snapchat', 'youtube'], true)
                    ? '@' . ltrim($handle, '@')
                    : $handle,
                default  => preg_replace('#^https?://(www\.)?#i', '', rtrim($link->url, '/')),
            };

            // Rebuild wa.me from normalised digits so a legacy "wa.me/0962…" still opens.
            $href = $link->platform === 'whatsapp' ? 'https://wa.me/' . $digits : $link->url;

            $out[] = [
                'kind'     => 'social',
                'platform' => $link->platform,
                'label'    => $meta['label'],
                'value'    => $value,
                'href'     => $href,
            ];
        }

        return $out;
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->area?->localizedName(),
            $this->governorate?->localizedName(),
            $this->country?->localizedName(),
            $this->address,
        ]);
        return implode('، ', $parts);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(BranchWorkingHour::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class)->orderBy('sort_order')->orderBy('name_en');
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function branchPayments(): HasMany
    {
        return $this->hasMany(BranchPayment::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function socialLinks(): MorphMany
    {
        return $this->morphMany(SocialLink::class, 'linkable');
    }

    public function images(): HasMany
    {
        return $this->hasMany(BranchImage::class)->orderBy('sort_order');
    }

    /**
     * Public-facing photos only: approved, with the cover first. This is what
     * the customer-facing marketplace should load — pending/rejected photos and
     * the raw sort order never leak to visitors.
     */
    public function approvedImages(): HasMany
    {
        return $this->hasMany(BranchImage::class)
            ->where('status', BranchImage::STATUS_APPROVED)
            ->orderByDesc('is_cover')
            ->orderBy('sort_order');
    }

    /** The designated cover, falling back to the first approved photo. */
    public function coverImage(): ?BranchImage
    {
        $loaded = $this->relationLoaded('approvedImages') ? $this->approvedImages : null;

        return ($loaded?->firstWhere('is_cover', true) ?? $loaded?->first())
            ?? $this->approvedImages()->first();
    }

    /**
     * Guarantee the branch has exactly one cover among its approved photos:
     * when none is flagged, promote the earliest approved one. Safe to call
     * after any change to a photo's status or after a delete.
     */
    public function ensureHasCover(): void
    {
        $hasCover = $this->images()
            ->where('is_cover', true)
            ->where('status', BranchImage::STATUS_APPROVED)
            ->exists();

        if ($hasCover) {
            return;
        }

        // Prefer a "place" photo for the cover (it represents the venue), then
        // fall back to work photos, then to sort order.
        $first = $this->images()
            ->where('status', BranchImage::STATUS_APPROVED)
            ->reorder() // drop the relationship's default sort_order so type can lead
            ->orderByRaw("CASE WHEN type = ? THEN 0 ELSE 1 END", [BranchImage::TYPE_PLACE])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($first) {
            $this->images()->update(['is_cover' => false]);
            $first->update(['is_cover' => true]);
        }
    }

    public function loyaltyRewards(): HasMany
    {
        return $this->hasMany(LoyaltyReward::class)->orderBy('sort_order')->orderBy('points_cost');
    }
}
