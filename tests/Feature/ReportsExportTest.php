<?php

namespace Tests\Feature;

use App\Exports\CoordinatorReportsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_export_builds_four_sheets(): void
    {
        $export = new CoordinatorReportsExport(1);
        $sheets = $export->sheets();

        $this->assertCount(4, $sheets);
        $titles = array_map(fn ($s) => $s->title(), $sheets);
        $this->assertContains('Avance por sesión', $titles);
        $this->assertContains('Fuera del cronograma', $titles);
    }

    public function test_export_stores_a_valid_xlsx(): void
    {
        Storage::fake('local');
        Excel::store(new CoordinatorReportsExport(1), 'reportes.xlsx', 'local');
        Storage::disk('local')->assertExists('reportes.xlsx');
    }
}
