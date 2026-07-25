<?php

namespace App\Filament\Pages;

use App\Exports\CoordinatorReportsExport;
use App\Models\User;
use App\Services\ReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes de avance';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 1;

    public Collection $sessionProgress;

    /** Per-program, per-session breakdown counted over every dupla. */
    public Collection $sessionBreakdown;

    public Collection $duplas;

    /** @var array<int,array{label:string,order:int,count:int,duplas:Collection}> */
    public array $currentGroups = [];

    /** @var array<string,int> */
    public array $counts = [];

    /** Superadmin, org-admin, coordinator and read-only client can see reports. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [
            User::ROLE_SUPERADMIN,
            User::ROLE_ORG_ADMIN,
            User::ROLE_COORDINATOR,
            User::ROLE_CLIENT,
        ], true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $user = auth()->user();
                    $orgId = $user->isSuperadmin() ? null : $user->organization_id;

                    return Excel::download(
                        new CoordinatorReportsExport($orgId, $user->canManageContent()),
                        'reportes-avance.xlsx',
                    );
                }),
        ];
    }

    public function mount(): void
    {
        $report = ReportService::forUser(auth()->user());

        $locale = app()->getLocale();

        $this->sessionProgress = $report->progressBySession();
        $this->sessionBreakdown = $report->sessionBreakdownByProgram();
        $this->duplas = $report->duplasBySchedule();
        $this->counts = [
            'dentro' => $this->duplas->where('category', 'dentro')->count(),
            'fuera' => $this->duplas->where('category', 'fuera')->count(),
            'sin_inicio' => $this->duplas->where('category', 'sin_inicio')->count(),
        ];

        // Report #1: duplas grouped by the session they are currently on.
        $this->currentGroups = $this->duplas
            ->groupBy(fn ($row) => $row['current']?->id ?? 'done')
            ->map(function ($rows) use ($locale) {
                $current = $rows->first()['current'];

                return [
                    'label' => $current
                        ? 'S'.$current->number.' · '.$current->getTranslation('name', $locale)
                        : 'Finalizado',
                    'order' => $current?->sort_order ?? 9999,
                    'count' => $rows->count(),
                    'duplas' => $rows->values(),
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();
    }

    public function duplasIn(string $category): Collection
    {
        return $this->duplas->where('category', $category)->values();
    }
}
