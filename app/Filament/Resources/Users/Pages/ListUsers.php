<?php

namespace App\Filament\Resources\Users\Pages;

use App\Exports\ImportTemplateExport;
use App\Exports\UsersExport;
use App\Filament\Resources\Users\UserResource;
use App\Imports\UsersImport;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
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
                ->modalDescription('Sube el archivo con las columnas de la plantilla. Descárgala con el botón "Descargar plantilla".')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel', 'text/csv',
                        ])
                        ->directory('imports')->storeFiles()->required(),
                ])
                ->action(function (array $data) use ($orgId) {
                    $import = new UsersImport($orgId);
                    Excel::import($import, Storage::disk('public')->path($data['file']));
                    Notification::make()->title("Usuarios importados: {$import->imported}")->success()->send();
                }),
        ];
    }
}
