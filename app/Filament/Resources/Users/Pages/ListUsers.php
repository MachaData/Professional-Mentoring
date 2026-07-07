<?php

namespace App\Filament\Resources\Users\Pages;

use App\Exports\ImportTemplateExport;
use App\Exports\UsersExport;
use App\Filament\Concerns\HandlesExcelImport;
use App\Filament\Resources\Users\UserResource;
use App\Imports\UsersImport;
use App\Models\Organization;
use App\Models\User;
use App\Services\UserInvitationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
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

            Action::make('inviteAll')
                ->label('Enviar invitación a todos')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->visible(fn () => $user->canManageContent())
                ->requiresConfirmation()
                ->modalHeading('Enviar invitación a todos')
                ->modalDescription(fn () => 'Se enviará la bienvenida con la contraseña temporal a '
                    .$this->pendingInvitees()->count().' usuario(s) (mentores y mentees que aún no activaron su cuenta). '
                    .'Los que ya la activaron se omiten para no reiniciar su contraseña.')
                ->action(function () {
                    @set_time_limit(300); // large one-off sends via a single-worker server
                    $service = app(UserInvitationService::class);
                    $sent = 0;
                    $failed = 0;

                    foreach ($this->pendingInvitees() as $invitee) {
                        try {
                            $service->invite($invitee);
                            $sent++;
                        } catch (\Throwable) {
                            $failed++;
                        }
                    }

                    Notification::make()
                        ->title("Invitaciones enviadas: {$sent}".($failed ? " · Fallidas: {$failed}" : ''))
                        ->success()
                        ->send();
                }),

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

    /**
     * Mentors/mentees that still need their welcome email: scoped to the admin's
     * organization and excluding accounts that already activated (so we never
     * reset a working password).
     *
     * @return Collection<int,User>
     */
    protected function pendingInvitees(): Collection
    {
        $user = auth()->user();

        return User::query()
            ->when(! $user->isSuperadmin(), fn ($q) => $q->where('organization_id', $user->organization_id))
            ->whereIn('role', [User::ROLE_FACILITATOR, User::ROLE_PARTICIPANT])
            ->whereNotNull('email')
            ->where(fn ($q) => $q->whereNull('invitation_status')->orWhere('invitation_status', '!=', 'active'))
            ->get();
    }
}
