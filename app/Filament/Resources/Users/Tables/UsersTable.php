<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Shared\PasswordAdminActions;
use App\Models\User;
use App\Services\PasswordAdministrationService;
use App\Services\UserInvitationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Password;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')->label('')->circular()->defaultImageUrl(
                    fn (User $r) => 'https://ui-avatars.com/api/?name='.urlencode($r->name)
                ),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('email')->label('Correo')->searchable()->copyable(),
                TextColumn::make('role')->label('Rol')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        User::ROLE_SUPERADMIN => 'Superadmin',
                        User::ROLE_ORG_ADMIN => 'Admin Org.',
                        User::ROLE_COORDINATOR => 'Coordinador',
                        User::ROLE_FACILITATOR => 'Facilitador',
                        User::ROLE_PARTICIPANT => 'Participante',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_SUPERADMIN => 'danger',
                        User::ROLE_ORG_ADMIN => 'warning',
                        User::ROLE_COORDINATOR => 'primary',
                        User::ROLE_FACILITATOR => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('organization.name')->label('Organización')->toggleable(),
                TextColumn::make('company')->label('Empresa')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invitation_status')->label('Invitación')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success', 'sent' => 'info', default => 'gray',
                    }),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rol')->options([
                    User::ROLE_SUPERADMIN => 'Superadmin',
                    User::ROLE_ORG_ADMIN => 'Admin Org.',
                    User::ROLE_COORDINATOR => 'Coordinador',
                    User::ROLE_FACILITATOR => 'Facilitador',
                    User::ROLE_PARTICIPANT => 'Participante',
                ]),
                SelectFilter::make('status')->label('Estado')->options([
                    'active' => 'Activo', 'inactive' => 'Inactivo',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('invite')
                    ->label('Enviar invitación')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('Se generará una contraseña temporal y se enviará el correo de bienvenida.')
                    ->visible(fn (User $record) => auth()->user()->canManageContent()
                        && in_array($record->role, [User::ROLE_FACILITATOR, User::ROLE_PARTICIPANT], true))
                    ->action(function (User $record) {
                        try {
                            app(UserInvitationService::class)->invite($record);
                            Notification::make()->title('Invitación enviada')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('No se pudo enviar')->body($e->getMessage())->danger()->send();
                        }
                    }),
                PasswordAdminActions::sendResetLink(),
                PasswordAdminActions::resetPassword(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('inviteBulk')
                        ->label('Enviar invitaciones')
                        ->icon('heroicon-o-envelope')
                        ->visible(fn () => auth()->user()->canManageContent())
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $service = app(UserInvitationService::class);
                            $sent = 0;
                            foreach ($records as $record) {
                                if (! in_array($record->role, [User::ROLE_FACILITATOR, User::ROLE_PARTICIPANT], true)) {
                                    continue;
                                }
                                try {
                                    $service->invite($record);
                                    $sent++;
                                } catch (\Throwable) {
                                }
                            }
                            Notification::make()->title("Invitaciones enviadas: {$sent}")->success()->send();
                        }),
                    BulkAction::make('sendResetLinkBulk')
                        ->label('Enviar enlaces de recuperación')
                        ->icon('heroicon-o-envelope-open')
                        ->visible(fn () => auth()->user()->canSuperviseDuplas())
                        ->requiresConfirmation()
                        ->modalDescription('Cada usuario seleccionado recibirá un enlace para crear una contraseña nueva. Las contraseñas actuales no cambian.')
                        ->action(function (Collection $records) {
                            $service = app(PasswordAdministrationService::class);
                            $sent = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (! PasswordAdminActions::maySendResetLink($record)) {
                                    $skipped++;

                                    continue;
                                }
                                $service->sendResetLink($record) === Password::RESET_LINK_SENT
                                    ? $sent++
                                    : $skipped++;
                            }

                            Notification::make()
                                ->title("Enlaces enviados: {$sent}".($skipped ? " · Omitidos: {$skipped}" : ''))
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
