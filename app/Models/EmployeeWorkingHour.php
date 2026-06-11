<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkingHour extends Model
{
    protected $fillable = [
        'employee_id',
        'day_of_week',
        'is_working',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_working'  => 'boolean',
        ];
    }

    public static array $dayNames = [
        0 => ['en' => 'Sunday',    'ar' => 'الأحد'],
        1 => ['en' => 'Monday',    'ar' => 'الاثنين'],
        2 => ['en' => 'Tuesday',   'ar' => 'الثلاثاء'],
        3 => ['en' => 'Wednesday', 'ar' => 'الأربعاء'],
        4 => ['en' => 'Thursday',  'ar' => 'الخميس'],
        5 => ['en' => 'Friday',    'ar' => 'الجمعة'],
        6 => ['en' => 'Saturday',  'ar' => 'السبت'],
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
