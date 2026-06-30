<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos generales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('domain')
                            ->label('Dominio')
                            ->placeholder('pro-mentoring.com'),
                        Select::make('default_locale')
                            ->label('Idioma por defecto')
                            ->options(['es' => 'Español', 'en' => 'English'])
                            ->default('es')
                            ->required(),
                        Toggle::make('is_operator')
                            ->label('Organización operadora')
                            ->helperText('CrossPartners Group opera la plataforma.'),
                        Select::make('status')
                            ->options(['active' => 'Activa', 'inactive' => 'Inactiva'])
                            ->default('active')
                            ->required(),
                    ]),

                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->directory('organizations/logos'),
                        FileUpload::make('login_background')
                            ->label('Fondo de login')
                            ->image()
                            ->directory('organizations/backgrounds'),
                        ColorPicker::make('primary_color')->label('Color principal')->default('#E30613'),
                        ColorPicker::make('secondary_color')->label('Color secundario')->default('#1A1A1A'),
                    ]),

                Section::make('Textos (ES / EN)')
                    ->columns(2)
                    ->schema([
                        Textarea::make('welcome_text.es')->label('Bienvenida (ES)')->rows(2),
                        Textarea::make('welcome_text.en')->label('Welcome (EN)')->rows(2),
                        Textarea::make('footer_text.es')->label('Pie de página (ES)')->rows(2),
                        Textarea::make('footer_text.en')->label('Footer (EN)')->rows(2),
                    ]),
            ]);
    }
}
