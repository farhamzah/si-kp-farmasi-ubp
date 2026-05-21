<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KpRecapExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function headings(): array
    {
        return array_keys($this->rows->first() ?? []);
    }

    public function array(): array
    {
        return $this->rows->map(fn ($row) => array_values($row))->all();
    }
}
