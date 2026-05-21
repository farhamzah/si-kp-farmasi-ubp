<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class UserTemplateExport implements FromArray
{
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }
}
