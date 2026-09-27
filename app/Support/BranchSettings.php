<?php

namespace App\Support;

use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * The vocabulary of the Branch Settings page: every allowed value, its label,
 * and the validation rules. The page, the controller and the booking engine all
 * read from here, so an option can never exist in the UI but fail validation
 * (or the other way round).
 *
 * Scope: the branch's clock and booking window only. Customer-facing rules
 * (online booking, same-day, cancel / reschedule) live in BookingPolicy.
 *
 * All durations are stored in minutes, except max_booking_days (days).
 */
final class BranchSettings
{
    public const TIME_FORMATS = ['24h', '12h'];

    public const INTERVALS = [5, 10, 15, 20, 30, 45, 60];

    /** Minimum notice before a booking can start (minutes). 0 = no restriction. */
    public const MIN_NOTICE = [0, 15, 30, 60, 120, 360, 720, 1440];

    /** How far ahead customers can book (days). */
    public const MAX_DAYS = [7, 14, 30, 60, 90, 180, 365];

    /** Carbon dayOfWeek values offered as the first day of the week. */
    public const FIRST_DAYS = [6, 0, 1];

    /**
     * Arabic names for the zones Booksy customers are most likely to pick.
     * Anything not listed falls back to the city part of the identifier.
     */
    private const AR_CITIES = [
        'Asia/Damascus'      => 'دمشق',
        'Asia/Dubai'         => 'دبي',
        'Asia/Riyadh'        => 'الرياض',
        'Asia/Amman'         => 'عمّان',
        'Asia/Beirut'        => 'بيروت',
        'Asia/Baghdad'       => 'بغداد',
        'Asia/Kuwait'        => 'الكويت',
        'Asia/Qatar'         => 'الدوحة',
        'Asia/Bahrain'       => 'البحرين',
        'Asia/Muscat'        => 'مسقط',
        'Asia/Aden'          => 'عدن',
        'Asia/Jerusalem'     => 'القدس',
        'Asia/Gaza'          => 'غزة',
        'Asia/Hebron'        => 'الخليل',
        'Asia/Istanbul'      => 'إسطنبول',
        'Europe/Istanbul'    => 'إسطنبول',
        'Asia/Tehran'        => 'طهران',
        'Asia/Karachi'       => 'كراتشي',
        'Asia/Kolkata'       => 'كولكاتا',
        'Africa/Cairo'       => 'القاهرة',
        'Africa/Tripoli'     => 'طرابلس',
        'Africa/Tunis'       => 'تونس',
        'Africa/Algiers'     => 'الجزائر',
        'Africa/Casablanca'  => 'الدار البيضاء',
        'Africa/Khartoum'    => 'الخرطوم',
        'Europe/London'      => 'لندن',
        'Europe/Amsterdam'   => 'أمستردام',
        'Europe/Berlin'      => 'برلين',
        'Europe/Paris'       => 'باريس',
        'Europe/Stockholm'   => 'ستوكهولم',
        'Europe/Brussels'    => 'بروكسل',
        'Europe/Vienna'      => 'فيينا',
        'Europe/Athens'      => 'أثينا',
        'Europe/Moscow'      => 'موسكو',
        'America/New_York'   => 'نيويورك',
        'America/Chicago'    => 'شيكاغو',
        'America/Los_Angeles'=> 'لوس أنجلوس',
        'America/Toronto'    => 'تورونتو',
        'Australia/Sydney'   => 'سيدني',
        'UTC'                => 'التوقيت العالمي',
    ];

    /** Validation rules for the settings form (keys = branch columns). */
    public static function rules(): array
    {
        return [
            'timezone'               => ['required', 'string', 'timezone:all'],
            'time_format'            => ['required', Rule::in(self::TIME_FORMATS)],
            'appointment_interval'   => ['required', 'integer', Rule::in(self::INTERVALS)],
            'min_booking_notice'     => ['required', 'integer', Rule::in(self::MIN_NOTICE)],
            'max_booking_days'       => ['required', 'integer', Rule::in(self::MAX_DAYS)],
            'first_day_of_week'      => ['required', 'integer', Rule::in(self::FIRST_DAYS)],
        ];
    }

    /** "30 minutes" / "2 hours" / "24 hours" — localized, for every minutes-based select. */
    public static function durationLabel(int $minutes): string
    {
        return $minutes >= 60 && $minutes % 60 === 0
            ? self::countLabel(intdiv($minutes, 60), 'hour')
            : self::countLabel($minutes, 'minute');
    }

    /** "7 days" / "30 days" — localized. */
    public static function daysLabel(int $days): string
    {
        return self::countLabel($days, 'day');
    }

    /**
     * Counted noun with correct Arabic number agreement:
     * 1 → singular + واحد, 2 → dual, 3–10 → plural, 11+ → singular accusative.
     */
    private static function countLabel(int $n, string $unit): string
    {
        if (app()->getLocale() !== 'ar') {
            return $n . ' ' . $unit . ($n === 1 ? '' : 's');
        }

        $forms = [
            // one, two, 3–10, 11+
            'minute' => ['دقيقة واحدة', 'دقيقتان', 'دقائق', 'دقيقة'],
            'hour'   => ['ساعة واحدة', 'ساعتان', 'ساعات', 'ساعة'],
            'day'    => ['يوم واحد', 'يومان', 'أيام', 'يوماً'],
        ][$unit];

        return match (true) {
            $n === 1 => $forms[0],
            $n === 2 => $forms[1],
            $n <= 10 => $n . ' ' . $forms[2],
            default  => $n . ' ' . $forms[3],
        };
    }

    public static function dayLabel(int $dow): string
    {
        return match ($dow) {
            6       => __('Saturday'),
            0       => __('Sunday'),
            1       => __('Monday'),
            default => (string) $dow,
        };
    }

    /**
     * Every IANA zone, grouped by region, with the city label and the current
     * UTC offset — the offset is computed for *now*, so DST zones read correctly.
     *
     * @return array<string, array<int, array{id:string, city:string, offset:string}>>
     */
    public static function timezoneGroups(): array
    {
        $groups = [];
        $now    = Carbon::now('UTC');

        foreach (DateTimeZone::listIdentifiers() as $id) {
            $region = str_contains($id, '/') ? strstr($id, '/', true) : 'Other';
            $groups[$region][] = [
                'id'     => $id,
                'city'   => self::cityName($id),
                'offset' => self::offsetLabel($id, $now),
            ];
        }

        ksort($groups);

        return $groups;
    }

    /** City label for a zone, e.g. "Asia/Damascus" → "دمشق" / "Damascus". */
    public static function cityName(string $id): string
    {
        if (app()->getLocale() === 'ar' && isset(self::AR_CITIES[$id])) {
            return self::AR_CITIES[$id];
        }
        if ($id === 'UTC') {
            return 'UTC';
        }

        $city = substr($id, (int) strrpos($id, '/') + 1);

        return str_replace('_', ' ', $city);
    }

    /** "UTC+03:00" for a zone at a given instant (defaults to now). */
    public static function offsetLabel(string $id, ?\DateTimeInterface $at = null): string
    {
        $seconds = (new DateTimeZone($id))->getOffset($at ?? new \DateTimeImmutable('now'));
        $sign    = $seconds < 0 ? '-' : '+';
        $seconds = abs($seconds);

        return sprintf('UTC%s%02d:%02d', $sign, intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }
}
