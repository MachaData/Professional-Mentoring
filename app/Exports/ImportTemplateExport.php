<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Generates a downloadable import template: the exact column headers expected by
 * an importer plus one example row to guide the user.
 */
class ImportTemplateExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int,string>  $headings
     * @param  array<int,array<int,string>>  $exampleRows
     */
    public function __construct(
        protected array $headings,
        protected array $exampleRows = [],
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->exampleRows;
    }
}
