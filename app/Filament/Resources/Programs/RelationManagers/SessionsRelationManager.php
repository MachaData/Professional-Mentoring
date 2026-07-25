<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'Sesiones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sesión')
                    ->columns(2)
                    ->schema([
                        TextInput::make('number')->label('Número')->numeric(),
                        Select::make('stage_id')->label('Etapa')
                            ->options(fn () => $this->getOwnerRecord()->stages()
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->getTranslation('name', app()->getLocale())])
                                ->toArray())
                            ->searchable(),
                        TextInput::make('name.es')->label('Nombre (ES)')->required(),
                        TextInput::make('name.en')->label('Name (EN)')->required(),
                        Textarea::make('objective.es')->label('Objetivo (ES)')->rows(2),
                        Textarea::make('objective.en')->label('Objective (EN)')->rows(2),
                        TextInput::make('survey_url')->label('Link de encuesta')->url()
                            ->placeholder('https://forms.gle/…')->columnSpanFull(),
                    ]),
                Section::make('Configuración')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')->label('Fecha de inicio'),
                        DatePicker::make('end_date')->label('Fecha de fin'),
                        Toggle::make('requires_registration')->label('Requiere registro')->default(true),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activa', 'inactive' => 'Inactiva', 'finished' => 'Finalizada'])
                            ->default('active')->required(),
                    ]),
                Section::make('Visibilidad y acceso')
                    ->columns(2)
                    ->schema([
                        Toggle::make('visible_to_participant')->label('Visible para el mentee')->default(true),
                        Toggle::make('visible_to_facilitator')->label('Visible para el mentor')->default(true),
                        Toggle::make('is_locked')->label('Bloqueada')->default(false)->live()
                            ->helperText('Si además es visible, se muestra como «Próximamente».'),
                        DatePicker::make('unlock_at')->label('Se desbloquea el')->native(false)
                            ->visible(fn (Get $get) => (bool) $get('is_locked')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sesiones')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('number')->label('#')->badge(),
                TextColumn::make('name')->label('Sesión')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),
                TextColumn::make('stage.name')->label('Etapa')
                    ->getStateUsing(fn ($record) => $record->stage?->getTranslation('name', app()->getLocale()))
                    ->badge()->color('gray')->placeholder('—'),
                TextColumn::make('objective')->label('Objetivo')
                    ->getStateUsing(fn ($record) => $record->getTranslation('objective', app()->getLocale()))
                    ->limit(40)->wrap(),
                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y'),
                TextColumn::make('end_date')->label('Fin')->date('d/m/Y'),
                IconColumn::make('visible_to_participant')->label('Mentee')->boolean(),
                IconColumn::make('visible_to_facilitator')->label('Mentor')->boolean(),
                TextColumn::make('access')->label('Acceso')->badge()
                    ->getStateUsing(fn ($record) => $record->isLocked() ? 'Bloqueada' : 'Abierta')
                    ->color(fn (string $state) => $state === 'Bloqueada' ? 'warning' : 'success'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'finished' => 'info', default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) {
                        $data['organization_id'] = $this->getOwnerRecord()->organization_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
