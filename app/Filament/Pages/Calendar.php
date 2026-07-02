<?php

namespace App\Filament\Pages;

use App\Models\Session;
use App\Services\CalendarService;
use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class Calendar extends Page
{
    protected string $view = 'filament.pages.calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Cronograma';

    protected static ?string $title = 'Cronograma';

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 2;

    public Carbon $month;
    /** @var array<string,array<int,array<string,mixed>>> */
    public array $events = [];
    /** @var array<string,int> */
    public array $counts = [];
    public string $prevUrl = '';
    public string $nextUrl = '';
    public string $todayUrl = '';

    /** Superadmin, org-admin and coordinator can see the schedule. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [
            \App\Models\User::ROLE_SUPERADMIN,
            \App\Models\User::ROLE_ORG_ADMIN,
            \App\Models\User::ROLE_COORDINATOR,
        ], true);
    }

    public function mount(CalendarService $calendar): void
    {
        $this->month = $calendar->month(request()->query('m'));

        // All org sessions (auto-scoped); each program's schedule shown once.
        $sessions = Session::query()->orderBy('sort_order')->get();
        $this->events = $calendar->events($sessions);

        $duplas = ReportService::forUser(auth()->user())->duplasBySchedule();
        $this->counts = [
            'duplas' => $duplas->count(),
            'dentro' => $duplas->where('category', 'dentro')->count(),
            'fuera' => $duplas->where('category', 'fuera')->count(),
            'sin_inicio' => $duplas->where('category', 'sin_inicio')->count(),
        ];

        $base = static::getUrl();
        $sep = str_contains($base, '?') ? '&' : '?';
        $this->prevUrl = $base.$sep.'m='.$this->month->copy()->subMonthNoOverflow()->format('Y-m');
        $this->nextUrl = $base.$sep.'m='.$this->month->copy()->addMonthNoOverflow()->format('Y-m');
        $this->todayUrl = $base;
    }
}
