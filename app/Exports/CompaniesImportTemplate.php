<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The blank .xlsx the owner downloads before a bulk import — the exact heading
 * row CompaniesImport expects, plus one example row so the format is obvious.
 */
class CompaniesImportTemplate implements WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return __('Companies');
    }

    /** @return array<int, array<int, string>> */
    public function headings(): array
    {
        // A real category name from THIS install, so the example row is valid.
        $sampleCategory = optional(Category::query()->orderBy('sort_order')->first())->localizedName() ?? 'Salon';

        return [
            ['name_en', 'name_ar', 'email', 'phone', 'category', 'plan', 'status', 'password'],
            ['Rose Beauty', 'روز بيوتي', 'rose@example.com', '+963991234567', $sampleCategory, '', 'pending', ''],
        ];
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4B5D34');
        // Grey-out the example row so it reads as guidance, not data.
        $sheet->getStyle('A2:H2')->getFont()->getColor()->setARGB('FF9AA090');

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
