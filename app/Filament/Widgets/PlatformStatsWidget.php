<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $report = ReportService::forUser(auth()->user());
        $sessions = $report->sessionStats();

        return [
            Stat::make('Programas', $report->programCount())
                ->description($report->programCount(true).' activos')
                ->color('primary'),
            Stat::make('Facilitadores', $report->userCount(User::ROLE_FACILITATOR))
                ->color('info'),
            Stat::make('Participantes', $report->userCount(User::ROLE_PARTICIPANT))
                ->description($report->participantsWithoutFacilitator().' sin asignar')
                ->color('info'),
            Stat::make('Asignaciones activas', $report->activeAssignments())
                ->color('success'),
            Stat::make('Sesiones completadas', $sessions['completed'])
                ->description($sessions['total'].' en total')
                ->color('success'),
            Stat::make('Sesiones pendientes', $sessions['pending'])
                ->color('warning'),
            Stat::make('Sesiones vencidas', $sessions['expired'])
                ->color('danger'),
        ];
    }
}
