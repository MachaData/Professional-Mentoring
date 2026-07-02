<?php

namespace App\Filament\Concerns;

/**
 * Configuration resources that coordinators should not see at all.
 */
trait HiddenFromCoordinator
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageContent() ?? false;
    }
}
