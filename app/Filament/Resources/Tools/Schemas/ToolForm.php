<?php

namespace App\Filament\Resources\Tools\Schemas;

use App\Models\Session;
use App\Models\Stage;
use App\Support\ResourceOptions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ToolForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Herramienta / material')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.es')->label('Nombre (ES)')->required(),
                        TextInput::make('name.en')->label('Name (EN)')->required(),
                        Select::make('type')->label('Tipo')
                            ->options(ResourceOptions::toolTypes())->default('other')->required(),
                        TextInput::make('category')->label('Categoría'),
                        Select::make('visibility')->label('Visibilidad')
                            ->options(ResourceOptions::visibility())->default('all')->required(),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activo', 'inactive' => 'Inactivo'])->default('active')->required(),
                        Textarea::make('description.es')->label('Descripción (ES)')->rows(2),
                        Textarea::make('description.en')->label('Description (EN)')->rows(2),
                        Select::make('organization_id')->label('Organización')
                            ->relationship('organization', 'name')->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()->required(),
                    ]),

                Section::make('Recurso')
                    ->columns(2)
                    ->schema([
                        TextInput::make('external_url')->label('Enlace externo')->url()
                            ->helperText('Drive, Google Forms, YouTube, etc.'),
                        FileUpload::make('file_path')->label('Archivo')->directory('tools'),
                    ]),

                Section::make('Asociaciones')
                    ->description('Dónde aparece esta herramienta. Cada fila la asocia a un programa, etapa o sesión.')
                    ->schema([
                        Repeater::make('relations')
                            ->relationship()
                            ->label('')
                            ->columns(3)
                            ->schema([
                                Select::make('program_id')->label('Programa')
                                    ->relationship('program', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                                    ->searchable()->preload()->live(),
                                Select::make('stage_id')->label('Etapa (opcional)')
                                    ->options(fn (Get $get) => Stage::query()->where('program_id', $get('program_id'))
                                        ->get()->mapWithKeys(fn (Stage $s) => [$s->id => $s->getTranslation('name', app()->getLocale())])->toArray())
                                    ->searchable(),
                                Select::make('session_id')->label('Sesión (opcional)')
                                    ->options(fn (Get $get) => Session::query()->where('program_id', $get('program_id'))
                                        ->get()->mapWithKeys(fn (Session $s) => [$s->id => $s->getTranslation('name', app()->getLocale())])->toArray())
                                    ->searchable(),
                            ])
                            ->addActionLabel('Agregar asociación'),
                    ]),
            ]);
    }
}
