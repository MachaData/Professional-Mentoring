<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Cuenta')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre completo')->required()->maxLength(255),
                        TextInput::make('email')->label('Correo')->email()->required()
                            ->unique(ignoreRecord: true),
                        Select::make('role')
                            ->label('Rol')
                            ->options([
                                User::ROLE_SUPERADMIN => 'Superadministrador',
                                User::ROLE_ORG_ADMIN => 'Administrador de organización',
                                User::ROLE_FACILITATOR => 'Facilitador',
                                User::ROLE_PARTICIPANT => 'Participante',
                            ])
                            // Org-admins cannot create superadmins.
                            ->disableOptionWhen(fn (string $value): bool => $value === User::ROLE_SUPERADMIN
                                && ! ($currentUser?->isSuperadmin() ?? false))
                            ->required()
                            ->live(),
                        Select::make('organization_id')
                            ->label('Organización')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            // Superadmin picks any org; org-admins are pinned to their own.
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()
                            ->visible(fn (callable $get) => $get('role') !== User::ROLE_SUPERADMIN)
                            ->required(fn (callable $get) => $get('role') !== User::ROLE_SUPERADMIN),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->helperText('Déjalo vacío al editar para mantener la actual.'),
                    ]),

                Section::make('Perfil')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('Celular / WhatsApp'),
                        FileUpload::make('photo')->label('Fotografía')->image()->avatar()
                            ->directory('users/photos'),
                        TextInput::make('position')->label('Cargo'),
                        TextInput::make('area')->label('Área'),
                        TextInput::make('business_unit')->label('Unidad de negocio'),
                        TextInput::make('company')->label('Empresa'),
                        Textarea::make('bio')->label('Breve descripción')->rows(3)->columnSpanFull(),
                    ]),

                Section::make('Preferencias')
                    ->columns(3)
                    ->schema([
                        TextInput::make('timezone')->label('Zona horaria')->default('America/Lima'),
                        Select::make('locale')->label('Idioma')
                            ->options(['es' => 'Español', 'en' => 'English'])->default('es'),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activo', 'inactive' => 'Inactivo'])->default('active'),
                    ]),
            ]);
    }
}
