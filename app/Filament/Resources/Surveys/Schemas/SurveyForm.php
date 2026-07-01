<?php

namespace App\Filament\Resources\Surveys\Schemas;

use App\Models\Session;
use App\Models\Stage;
use App\Support\ResourceOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Encuesta')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.es')->label('Nombre (ES)')->required(),
                        TextInput::make('name.en')->label('Name (EN)')->required(),
                        TextInput::make('external_url')->label('Enlace (Google Forms)')->url()->required()
                            ->columnSpanFull(),
                        Select::make('visible_to')->label('Visible para')
                            ->options(ResourceOptions::visibility())->default('participant')->required(),
                        Select::make('display_moment')->label('Momento')
                            ->options(ResourceOptions::displayMoment())->default('after_session')->required(),
                        Textarea::make('description.es')->label('Descripción (ES)')->rows(2),
                        Textarea::make('description.en')->label('Description (EN)')->rows(2),
                    ]),

                Section::make('Alcance')
                    ->columns(3)
                    ->schema([
                        Select::make('program_id')->label('Programa')
                            ->relationship('program', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()->required()->live(),
                        Select::make('stage_id')->label('Etapa (opcional)')
                            ->options(fn (Get $get) => Stage::query()->where('program_id', $get('program_id'))
                                ->get()->mapWithKeys(fn (Stage $s) => [$s->id => $s->getTranslation('name', app()->getLocale())])->toArray())
                            ->searchable(),
                        Select::make('session_id')->label('Sesión (opcional)')
                            ->options(fn (Get $get) => Session::query()->where('program_id', $get('program_id'))
                                ->get()->mapWithKeys(fn (Session $s) => [$s->id => $s->getTranslation('name', app()->getLocale())])->toArray())
                            ->searchable(),
                        Select::make('organization_id')->label('Organización')
                            ->relationship('organization', 'name')->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()->required(),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activa', 'inactive' => 'Inactiva'])->default('active')->required(),
                    ]),
            ]);
    }
}
