<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\UserInvitationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

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
                        User::ROLE_FACILITATOR => 'Facilitador',
                        User::ROLE_PARTICIPANT => 'Participante',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_SUPERADMIN => 'danger',
                        User::ROLE_ORG_ADMIN => 'warning',
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
                    User::ROLE_FACILITATOR => 'Facilitador',
                    User::ROLE_PARTICIPANT => 'Participante',
                ]),
                SelectFilter::make('status')->label('Estado')->options([
                    'active' => 'Activo', 'inactive' => 'Inactivo',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('invite')
                    ->label('Enviar invitación')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('Se generará una contraseña temporal y se enviará el correo de bienvenida.')
                    ->visible(fn (User $record) => in_array($record->role, [User::ROLE_FACILITATOR, User::ROLE_PARTICIPANT], true))
                    ->action(function (User $record) {
                        try {
                            app(UserInvitationService::class)->invite($record);
                            Notification::make()->title('Invitación enviada')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('No se pudo enviar')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('inviteBulk')
                        ->label('Enviar invitaciones')
                        ->icon('heroicon-o-envelope')
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
