<?php

namespace App\Filament\Resources\ProgramTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProgramTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Tipo de programa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.es')->label('Nombre (ES)')->required(),
                        TextInput::make('name.en')->label('Name (EN)')->required(),
                        Textarea::make('description.es')->label('Descripción (ES)')->rows(2),
                        Textarea::make('description.en')->label('Description (EN)')->rows(2),
                        Select::make('organization_id')
                            ->label('Organización')
                            ->relationship('organization', 'name')
                            ->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()
                            ->helperText('Vacío = catálogo global disponible para todas las organizaciones.'),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activo', 'inactive' => 'Inactivo'])
                            ->default('active')->required(),
                    ]),

                Section::make('Nombres visibles de roles')
                    ->description('Cómo se mostrarán facilitador y participante en este tipo de programa.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facilitator_label.es')->label('Facilitador (ES)')->required()->placeholder('Mentor'),
                        TextInput::make('facilitator_label.en')->label('Facilitator (EN)')->required()->placeholder('Mentor'),
                        TextInput::make('participant_label.es')->label('Participante (ES)')->required()->placeholder('Mentee'),
                        TextInput::make('participant_label.en')->label('Participant (EN)')->required()->placeholder('Mentee'),
                    ]),
            ]);
    }
}
