<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
