<?php

namespace App\Filament\Resources\ProgramTypes\Pages;

use App\Filament\Resources\ProgramTypes\ProgramTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProgramType extends EditRecord
{
    protected static string $resource = ProgramTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
