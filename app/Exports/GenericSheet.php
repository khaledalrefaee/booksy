<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * شيت إكسل واحد مبنيّ من بيانات كيان مجمّعة (label + headings + rows).
 */
class GenericSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int,string>                 $headings
     * @param  array<int,array<string,mixed>>    $rows
     */
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
    ) {
    }

    public function array(): array
    {
        // نضمن ترتيب القيم حسب العناوين تماماً.
        return array_map(
            fn (array $row) => array_map(
                fn (string $col) => $row[$col] ?? null,
                $this->headings
            ),
            $this->rows
        );
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->sanitizeTitle($this->title);
    }

    /**
     * عناوين شيتات إكسل: 31 حرف كحد أقصى وممنوع بعض الرموز.
     */
    private function sanitizeTitle(string $title): string
    {
        $title = str_replace(['\\', '/', '?', '*', ':', '[', ']'], ' ', $title);
        $title = trim(preg_replace('/\s+/u', ' ', $title));

        if (mb_strlen($title) > 31) {
            $title = mb_substr($title, 0, 31);
        }

        return $title !== '' ? $title : 'Sheet';
    }
}
