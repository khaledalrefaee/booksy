<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Read month/year from the query string, clamped to sane values so
     * garbage input (?month=2085456) can't crash date construction.
     *
     * @return array{0: int, 1: int} [month, year]
     */
    protected function safeMonthYear(Request $request): array
    {
        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year', now()->year);

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        return [$month, $year];
    }
}
