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
    public function __construct(protected ?int $organizationId = null) {}

    public function sheets(): array
    {
        $report = new ReportService($this->organizationId);
        $locale = app()->getLocale();
        $duplas = $report->duplasBySchedule();

        $name = fn ($model) => $model?->getTranslation('name', $locale);
        $sLabel = fn ($session) => $session ? 'S'.$session->number.' · '.$name($session) : 'Finalizado';

        // #1 Avance por sesión: duplas por sesión actual.
        $porSesion = $duplas
            ->sortBy(fn ($r) => $r['current']?->sort_order ?? 9999)
            ->map(fn ($r) => [
                $sLabel($r['current']),
                $r['assignment']->facilitator?->name,
                $r['assignment']->participant?->name,
                $name($r['assignment']->program),
                $r['completed'].'/'.$r['total'],
            ])->values()->all();

        // #2 Dentro del cronograma
        $dentro = $duplas->where('category', 'dentro')->map(fn ($r) => [
            $r['assignment']->facilitator?->name,
            $r['assignment']->participant?->name,
            $name($r['assignment']->program),
            $r['completed'].'/'.$r['total'],
        ])->values()->all();

        // #3 Fuera del cronograma (con detalle de atraso)
        $fuera = $duplas->where('category', 'fuera')->map(fn ($r) => [
            $r['assignment']->facilitator?->name,
            $r['assignment']->participant?->name,
            $r['expected'] ? 'S'.$r['expected']->number : '—',
            $r['current'] ? 'S'.$r['current']->number : '—',
            $r['days_behind'],
        ])->values()->all();

        // #4 Sin inicio
        $sinInicio = $duplas->where('category', 'sin_inicio')->map(fn ($r) => [
            $r['assignment']->facilitator?->name,
            $r['assignment']->participant?->name,
            $name($r['assignment']->program),
        ])->values()->all();

        return [
            new ArraySheet('Avance por sesión',
                ['Sesión actual', 'Facilitador', 'Participante', 'Programa', 'Avance'], $porSesion),
            new ArraySheet('Dentro del cronograma',
                ['Facilitador', 'Participante', 'Programa', 'Avance'], $dentro),
            new ArraySheet('Fuera del cronograma',
                ['Facilitador', 'Participante', 'Debería estar', 'Sesión actual', 'Días de atraso'], $fuera),
            new ArraySheet('Sin inicio',
                ['Facilitador', 'Participante', 'Programa'], $sinInicio),
        ];
    }
}
