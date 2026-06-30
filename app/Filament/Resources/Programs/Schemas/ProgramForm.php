<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Models\ProgramType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Programa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.es')->label('Nombre (ES)')->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('name.en')->label('Name (EN)')->required(),
                        TextInput::make('slug')->required(),
                        Select::make('status')->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'active' => 'Activo',
                                'paused' => 'Pausado',
                                'finished' => 'Finalizado',
                                'archived' => 'Archivado',
                            ])->default('draft')->required(),
                        Textarea::make('description.es')->label('Descripción (ES)')->rows(2),
                        Textarea::make('description.en')->label('Description (EN)')->rows(2),
                    ]),

                Section::make('Cliente y tipo')
                    ->columns(2)
                    ->schema([
                        Select::make('organization_id')
                            ->label('Organización')
                            ->relationship('organization', 'name')
                            ->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()
                            ->required()
                            ->live(),
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()->preload(),
                        Select::make('program_type_id')
                            ->label('Tipo de programa')
                            ->relationship('programType', 'id')
                            ->getOptionLabelFromRecordUsing(fn (ProgramType $r) => $r->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $type = ProgramType::find($state);
                                if ($type) {
                                    $set('facilitator_label.es', $type->getTranslation('facilitator_label', 'es', false));
                                    $set('facilitator_label.en', $type->getTranslation('facilitator_label', 'en', false));
                                    $set('participant_label.es', $type->getTranslation('participant_label', 'es', false));
                                    $set('participant_label.en', $type->getTranslation('participant_label', 'en', false));
                                }
                            }),
                        Select::make('default_locale')->label('Idioma por defecto')
                            ->options(['es' => 'Español', 'en' => 'English'])->default('es'),
                    ]),

                Section::make('Nombres visibles de roles')
                    ->description('Heredados del tipo de programa; puedes personalizarlos aquí.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facilitator_label.es')->label('Facilitador (ES)')->placeholder('Mentor'),
                        TextInput::make('facilitator_label.en')->label('Facilitator (EN)')->placeholder('Mentor'),
                        TextInput::make('participant_label.es')->label('Participante (ES)')->placeholder('Mentee'),
                        TextInput::make('participant_label.en')->label('Participant (EN)')->placeholder('Mentee'),
                    ]),

                Section::make('Fechas y branding')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')->label('Fecha de inicio'),
                        DatePicker::make('end_date')->label('Fecha de fin'),
                        FileUpload::make('logo')->label('Logo')->image()->directory('programs/logos'),
                        ColorPicker::make('primary_color')->label('Color principal'),
                        ColorPicker::make('secondary_color')->label('Color secundario'),
                    ]),
            ]);
    }
}
