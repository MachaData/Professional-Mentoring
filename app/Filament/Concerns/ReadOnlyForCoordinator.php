<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Makes a resource read-only for coordinators: they may view/list records but
 * cannot create, edit, delete or reorder. Superadmins and org-admins keep full
 * access.
 */
trait ReadOnlyForCoordinator
{
    public static function canCreate(): bool
    {
        return static::userCanManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::userCanManage();
    }

    public static function canDelete(Model $record): bool
    {
        return static::userCanManage();
    }

    public static function canDeleteAny(): bool
    {
        return static::userCanManage();
    }

    public static function canReorder(): bool
    {
        return static::userCanManage();
    }

    protected static function userCanManage(): bool
    {
        return auth()->user()?->canManageContent() ?? false;
    }
}
