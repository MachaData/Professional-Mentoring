<?php

namespace App\Filament\Resources\ProgramTypes\Pages;

use App\Filament\Resources\ProgramTypes\ProgramTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramTypes extends ListRecords
{
    protected static string $resource = ProgramTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
