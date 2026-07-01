<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class SessionsStatusChart extends ChartWidget
{
    protected ?string $heading = 'Sesiones por estado';

    protected function getData(): array
    {
        $stats = ReportService::forUser(auth()->user())->sessionStats();

        return [
            'datasets' => [[
                'label' => 'Sesiones',
                'data' => [$stats['completed'], $stats['pending'], $stats['expired']],
                'backgroundColor' => ['#16a34a', '#f59e0b', '#dc2626'],
            ]],
            'labels' => ['Completadas', 'Pendientes', 'Vencidas'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
