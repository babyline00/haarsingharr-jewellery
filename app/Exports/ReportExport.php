<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Generic report sheet: headings + rows provided by ReportController::rowsFor().
 */
class ReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $sheetName = 'Report',
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->sheetName;
    }
}
