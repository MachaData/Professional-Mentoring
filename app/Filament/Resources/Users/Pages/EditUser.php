<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Shared\PasswordAdminActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PasswordAdminActions::sendResetLink(),
            PasswordAdminActions::resetPassword(),
            DeleteAction::make(),
        ];
    }
}
