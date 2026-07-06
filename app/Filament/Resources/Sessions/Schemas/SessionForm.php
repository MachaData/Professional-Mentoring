<?php

namespace App\Filament\Resources\Sessions\Schemas;

use App\Models\Stage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sesión')
                    ->columns(2)
                    ->schema([
                        Select::make('program_id')->label('Programa')
                            ->relationship('program', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()->required()->live(),
                        Select::make('stage_id')->label('Etapa')
                            ->options(fn (Get $get) => Stage::query()
                                ->where('program_id', $get('program_id'))
                                ->get()
                                ->mapWithKeys(fn (Stage $s) => [$s->id => $s->getTranslation('name', app()->getLocale())])
                                ->toArray())
                            ->searchable(),
                        TextInput::make('number')->label('Número')->numeric(),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activa', 'inactive' => 'Inactiva', 'finished' => 'Finalizada'])
                            ->default('active')->required(),
                        TextInput::make('name.es')->label('Nombre (ES)')->required(),
                        TextInput::make('name.en')->label('Name (EN)')->required(),
                        Textarea::make('objective.es')->label('Objetivo (ES)')->rows(2),
                        Textarea::make('objective.en')->label('Objective (EN)')->rows(2),
                        TextInput::make('survey_url')->label('Link de encuesta')->url()
                            ->placeholder('https://forms.gle/…')->columnSpanFull()
                            ->helperText('Encuesta propia de esta sesión (Google Forms u otro).'),
                        Select::make('form_template_id')->label('Plantilla de formulario')
                            ->relationship('formTemplate', 'name')
                            ->searchable()->preload()->columnSpanFull()
                            ->helperText('Al crear la sesión se copiarán automáticamente los campos de la plantilla elegida (nombre, tipo, orden, obligatorio y visibilidad). Para reaplicarla al editar, usa el botón «Aplicar plantilla» en la sección de campos.'),
                    ]),
                Section::make('Configuración')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')->label('Fecha de inicio'),
                        DatePicker::make('end_date')->label('Fecha de fin'),
                        Toggle::make('requires_registration')->label('Requiere registro')->default(true),
                        Toggle::make('visible_to_participant')->label('Visible para participante')->default(true),
                    ]),
            ]);
    }
}
