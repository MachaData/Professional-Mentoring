<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet workbook with the coordinator's follow-up reports:
 * avance por sesión, y duplas dentro / fuera / sin inicio del cronograma.
 */
class CoordinatorReportsExport implements WithMultipleSheets
{
    public function __construct(
        protected ?int $organizationId = null,
        protected bool $includeDeactivated = true,
    ) {}

    public function sheets(): array
    {
        $report = new ReportService($this->organizationId, $this->includeDeactivated);
        $locale = app()->getLocale();
        $duplas = $report->duplasBySchedule();

        $name = fn ($model) => $model?->getTranslation('name', $locale);
        $sLabel = fn ($session) => $session ? 'S'.$session->number.' · '.$name($session) : 'Finalizado';
        $sShort = fn ($session) => $session ? 'S'.$session->number : '—';
        $activity = fn ($r) => $r['last_activity']
            ? $r['last_activity']->format('d/m/Y H:i').' · '.$r['last_activity_type']
            : 'Sin actividad';

        // Every dupla list shares these columns so the sheets read alike.
        $duplaColumns = ['Mentor', 'Mentee', 'Programa', 'Sesión actual', 'Sesión esperada',
            'Avance', 'Estado', 'Última actividad', 'Días de atraso'];
        $duplaRow = fn ($r) => [
            $r['assignment']->facilitator?->name,
            $r['assignment']->participant?->name,
            $name($r['assignment']->program),
            $sShort($r['current']),
            $sShort($r['expected']),
            $r['completed'].'/'.$r['total'],
            $r['state_label'],
            $activity($r),
            $r['days_behind'] ?: '',
        ];

        // #0 Desglose por sesión sobre el total de duplas (realizadas/pendientes/vencidas),
        // con una fila de totales por programa.
        $desglose = [];
        foreach ($report->sessionBreakdownByProgram() as $prog) {
            foreach ($prog['sessions'] as $r) {
                $desglose[] = [
                    $name($prog['program']),
                    'S'.$r['session']->number.' · '.$r['session']->getTranslation('name', $locale),
                    $r['total'], $r['completed'], $r['pending'], $r['expired'], $r['percent'].'%',
                ];
            }
            $desglose[] = [
                $name($prog['program']), 'TOTALES', $prog['total_duplas'],
                $prog['sessions']->sum('completed'),
                $prog['sessions']->sum('pending'),
                $prog['sessions']->sum('expired'), '',
            ];
        }

        // #1 Avance por sesión: duplas por sesión actual.
        $porSesion = $duplas
            ->sortBy(fn ($r) => $r['current']?->sort_order ?? 9999)
            ->map(fn ($r) => array_merge([$sLabel($r['current'])], $duplaRow($r)))
            ->values()->all();

        // #2/#3/#4 Duplas por estado de cronograma, mismas columnas en las tres.
        $porCategoria = fn (string $category) => $duplas
            ->where('category', $category)->map($duplaRow)->values()->all();

        return [
            new ArraySheet('Desglose por sesión',
                ['Programa', 'Sesión', 'Total duplas', 'Realizadas', 'Pendientes', 'Vencidas', '% completado'], $desglose),
            new ArraySheet('Avance por sesión',
                array_merge(['Sesión actual'], $duplaColumns), $porSesion),
            new ArraySheet('Dentro del cronograma', $duplaColumns, $porCategoria('dentro')),
            new ArraySheet('Fuera del cronograma', $duplaColumns, $porCategoria('fuera')),
            new ArraySheet('Sin inicio', $duplaColumns, $porCategoria('sin_inicio')),
            // Informe individual por dupla: una fila por dupla con todo el detalle,
            // el mismo que muestra la ficha de la dupla en el panel.
            new ArraySheet('Informe por dupla', $duplaColumns,
                $duplas->sortBy(fn ($r) => $r['assignment']->facilitator?->name)->map($duplaRow)->values()->all()),
        ];
    }
}
