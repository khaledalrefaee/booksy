<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyHoliday extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_paid'    => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function daysCount(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** The holiday covering the given date for this company, if any. */
    public static function coveringDate(int $companyId, Carbon|string $date): ?self
    {
        $d = $date instanceof Carbon ? $date->toDateString() : $date;

        return static::where('company_id', $companyId)
            ->where('start_date', '<=', $d)
            ->where('end_date', '>=', $d)
            ->first();
    }

    /** Set of holiday dates (Y-m-d strings) for the company within the given range. */
    public static function datesInRange(int $companyId, Carbon $from, Carbon $to): array
    {
        $dates = [];

        static::where('company_id', $companyId)
            ->where('start_date', '<=', $to->toDateString())
            ->where('end_date', '>=', $from->toDateString())
            ->get()
            ->each(function (self $holiday) use ($from, $to, &$dates) {
                $start = $holiday->start_date->greaterThan($from) ? $holiday->start_date->copy() : $from->copy();
                $end   = $holiday->end_date->lessThan($to) ? $holiday->end_date->copy() : $to->copy();
                for ($d = $start->copy()->startOfDay(); $d->lte($end); $d->addDay()) {
                    $dates[$d->toDateString()] = $holiday->name;
                }
            });

        return $dates;
    }
}
