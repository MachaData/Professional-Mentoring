<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Resources that supervisors (admins + coordinators) may create and edit so
 * they can follow up and help mentors/mentees. Deleting stays limited to admins
 * to avoid coordinators removing people or duplas by accident.
 */
trait ManagedBySupervisor
{
    public static function canCreate(): bool
    {
        return static::isSupervisor();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isSupervisor();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canManageContent() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canManageContent() ?? false;
    }

    public static function canReorder(): bool
    {
        return static::isSupervisor();
    }

    protected static function isSupervisor(): bool
    {
        return auth()->user()?->canSuperviseDuplas() ?? false;
    }
}
