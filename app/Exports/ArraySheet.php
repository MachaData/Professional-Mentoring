<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** A single titled sheet built from an array of rows + headings. */
class ArraySheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int,string>  $headings
     * @param  array<int,array<int,mixed>>  $rows
     */
    public function __construct(
        protected string $title,
        protected array $headings,
        protected array $rows,
    ) {}

    public function title(): string
    {
        // Excel sheet titles are capped at 31 chars and forbid some symbols.
        return mb_substr(str_replace(['\\', '/', '*', '?', ':', '[', ']'], ' ', $this->title), 0, 31);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
