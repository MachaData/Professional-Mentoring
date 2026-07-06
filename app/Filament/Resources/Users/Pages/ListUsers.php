<?php

namespace App\Filament\Resources\Users\Pages;

use App\Exports\ImportTemplateExport;
use App\Exports\UsersExport;
use App\Filament\Concerns\HandlesExcelImport;
use App\Filament\Resources\Users\UserResource;
use App\Imports\UsersImport;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    use HandlesExcelImport;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $orgId = $user->organization_id ?? Organization::query()->value('id');

        return [
            CreateAction::make(),

            Action::make('export')
                ->label('Exportar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new UsersExport($user->isSuperadmin() ? null : $user->organization_id),
                    'usuarios.xlsx'
                )),

            Action::make('downloadTemplate')
                ->label('Descargar plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ImportTemplateExport(UsersImport::templateHeadings(), UsersImport::templateExample()),
                    'plantilla-usuarios.xlsx'
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
                    new UsersImport($orgId),
                    'Usuarios importados',
                )),
        ];
    }
}
