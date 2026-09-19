<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * دفتر إكسل متعدد الشيتات لبيانات شركة، شيت لكل كيان.
 */
class CompanyDataWorkbook implements WithMultipleSheets
{
    /**
     * @param  array<string, array{label:string, headings:array<int,string>, rows:array}>  $collected
     */
    public function __construct(private array $collected)
    {
    }

    public function sheets(): array
    {
        $sheets = [];
        $usedTitles = [];

        foreach ($this->collected as $entity) {
            $label = (string) ($entity['label'] ?? 'Sheet');

            // ضمان تفرّد اسم الشيت (تفادي تصادم بعد القص لـ 31 حرفاً).
            $title = $label;
            $i = 2;
            while (in_array(mb_substr($title, 0, 31), $usedTitles, true)) {
                $title = $label . ' ' . $i++;
            }
            $usedTitles[] = mb_substr($title, 0, 31);

            $sheets[] = new GenericSheet(
                $title,
                $entity['headings'] ?? [],
                $entity['rows'] ?? [],
            );
        }

        return $sheets;
    }
}
