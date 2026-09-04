<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Owner → Companies export. Receives an ALREADY-FILTERED collection from the
 * controller (same query the listing uses) so the .xlsx always mirrors exactly
 * what the admin is looking at, filters included.
 */
class CompaniesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    /** @param Collection<int, \App\Models\Company> $companies */
    public function __construct(private Collection $companies)
    {
    }

    public function collection(): Collection
    {
        return $this->companies;
    }

    public function title(): string
    {
        return __('Companies');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            '#',
            __('Company'),
            __('Name (Arabic)'),
            __('Owner'),
            __('Email'),
            __('Phone'),
            __('Category'),
            __('Plan'),
            __('Status'),
            __('Created at'),
        ];
    }

    /**
     * @param \App\Models\Company $company
     * @return array<int, string>
     */
    public function map($company): array
    {
        $statusLabels = [
            'pending'   => __('Pending'),
            'active'    => __('Active'),
            'suspended' => __('Suspended'),
        ];

        return [
            $company->id,
            $company->name_en ?: '—',
            $company->name_ar ?: '—',
            $company->owner_name ?: '—',
            $company->email ?: '—',
            $company->phone ?: '—',
            $company->category?->localizedName() ?? '—',
            $company->plan?->localizedName() ?? __('No plan'),
            $statusLabels[$company->status] ?? $company->status,
            optional($company->created_at)->format('Y-m-d H:i') ?? '—',
        ];
    }

    /** @return array<int|string, mixed> */
    public function styles(Worksheet $sheet): array
    {
        // Bold header row on a deep-olive fill to match the GlowRez identity.
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4B5D34');

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
