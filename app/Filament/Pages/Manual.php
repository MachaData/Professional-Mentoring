<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Manual extends Page
{
    protected string $view = 'filament.pages.manual';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Manual de uso';

    protected static ?string $title = 'Manual del administrador';

    protected static string|\UnitEnum|null $navigationGroup = 'Ayuda';

    protected static ?int $navigationSort = 99;

    /** Everyone who can reach the admin panel can read the manual. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [
            User::ROLE_SUPERADMIN,
            User::ROLE_ORG_ADMIN,
            User::ROLE_COORDINATOR,
        ], true);
    }
}
