<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Cliente')
                    ->columns(2)
                    ->schema([
                        Select::make('organization_id')
                            ->label('Organización')
                            ->relationship('organization', 'name')
                            ->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()
                            ->required(),
                        TextInput::make('name')->label('Nombre')->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')->required(),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activo', 'inactive' => 'Inactivo'])
                            ->default('active')->required(),
                    ]),

                Section::make('Branding del cliente')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo')->label('Logo')->image()->directory('clients/logos'),
                        FileUpload::make('login_background')->label('Fondo de login')->image()
                            ->directory('clients/backgrounds'),
                        ColorPicker::make('primary_color')->label('Color principal'),
                        ColorPicker::make('secondary_color')->label('Color secundario'),
                        Textarea::make('welcome_text.es')->label('Bienvenida (ES)')->rows(2),
                        Textarea::make('welcome_text.en')->label('Welcome (EN)')->rows(2),
                    ]),
            ]);
    }
}
