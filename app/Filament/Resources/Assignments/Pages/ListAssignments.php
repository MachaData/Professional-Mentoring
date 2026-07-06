<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Exports\ImportTemplateExport;
use App\Filament\Concerns\HandlesExcelImport;
use App\Filament\Resources\Assignments\AssignmentResource;
use App\Imports\AssignmentsImport;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListAssignments extends ListRecords
{
    use HandlesExcelImport;

    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $orgId = $user->organization_id ?? Organization::query()->value('id');

        return [
            CreateAction::make(),

            Action::make('downloadTemplate')
                ->label('Descargar plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ImportTemplateExport(AssignmentsImport::templateHeadings(), AssignmentsImport::templateExample()),
                    'plantilla-asignaciones.xlsx'
                )),

            Action::make('import')
                ->label('Importar Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->visible(fn () => auth()->user()->canManageContent())
                ->modalDescription('Sube el archivo con las columnas de la plantilla. Descárgala con el botón "Descargar plantilla".')
                ->form([
                    $this->importFileUploadField(),
                ])
                ->action(fn (array $data) => $this->runExcelImport(
                    $data,
                    new AssignmentsImport($orgId),
                    'Asignaciones importadas',
                )),
        ];
    }
}
