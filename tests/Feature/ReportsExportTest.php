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

    public function test_export_builds_all_sheets(): void
    {
        $export = new CoordinatorReportsExport(1);
        $sheets = $export->sheets();

        $this->assertCount(6, $sheets);
        $titles = array_map(fn ($s) => $s->title(), $sheets);
        $this->assertContains('Desglose por sesión', $titles);
        $this->assertContains('Avance por sesión', $titles);
        $this->assertContains('Dentro del cronograma', $titles);
        $this->assertContains('Fuera del cronograma', $titles);
        $this->assertContains('Sin inicio', $titles);
        $this->assertContains('Informe por dupla', $titles);
    }

    /** Every dupla sheet carries the same follow-up columns. */
    public function test_dupla_sheets_share_the_follow_up_columns(): void
    {
        $sheets = collect((new CoordinatorReportsExport(1))->sheets())
            ->keyBy(fn ($sheet) => $sheet->title());

        foreach (['Dentro del cronograma', 'Fuera del cronograma', 'Sin inicio', 'Informe por dupla'] as $title) {
            $this->assertSame(
                ['Mentor', 'Mentee', 'Programa', 'Sesión actual', 'Sesión esperada',
                    'Avance', 'Estado', 'Última actividad', 'Días de atraso'],
                $sheets[$title]->headings(),
                "La hoja «{$title}» debe usar las columnas de seguimiento.",
            );
        }
    }

    public function test_export_stores_a_valid_xlsx(): void
    {
        Storage::fake('local');
        Excel::store(new CoordinatorReportsExport(1), 'reportes.xlsx', 'local');
        Storage::disk('local')->assertExists('reportes.xlsx');
    }
}
