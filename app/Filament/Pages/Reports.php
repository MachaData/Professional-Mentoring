<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes de avance';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 1;

    public Collection $sessionProgress;
    public Collection $duplas;
    /** @var array<string,int> */
    public array $counts = [];

    /** Superadmin, org-admin and coordinator can see reports. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [
            \App\Models\User::ROLE_SUPERADMIN,
            \App\Models\User::ROLE_ORG_ADMIN,
            \App\Models\User::ROLE_COORDINATOR,
        ], true);
    }

    public function mount(): void
    {
        $report = ReportService::forUser(auth()->user());

        $this->sessionProgress = $report->progressBySession();
        $this->duplas = $report->duplasBySchedule();
        $this->counts = [
            'dentro' => $this->duplas->where('category', 'dentro')->count(),
            'fuera' => $this->duplas->where('category', 'fuera')->count(),
            'sin_inicio' => $this->duplas->where('category', 'sin_inicio')->count(),
        ];
    }

    public function duplasIn(string $category): Collection
    {
        return $this->duplas->where('category', $category)->values();
    }
}
