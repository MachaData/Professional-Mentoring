<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\Session;
use App\Services\SessionRecordProvisioner;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Extra sessions added for this dupla only (beyond the program curriculum).
 * Coordinators and admins can create/edit/delete them; each new session gets a
 * pending record provisioned automatically.
 */
class ExtraSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'extraSessions';

    protected static ?string $title = 'Sesiones adicionales';

    protected static ?string $modelLabel = 'sesión adicional';

    protected static ?string $pluralModelLabel = 'sesiones adicionales';

    public function isReadOnly(): bool
    {
        return ! (auth()->user()?->canSuperviseDuplas() ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name.es')->label('Nombre (ES)')->required(),
            TextInput::make('name.en')->label('Nombre (EN)'),
            Textarea::make('objective.es')->label('Objetivo (ES)')->rows(2),
            Textarea::make('objective.en')->label('Objetivo (EN)')->rows(2),
            DatePicker::make('start_date')->label('Fecha inicio')->native(false),
            DatePicker::make('end_date')->label('Fecha cierre')->native(false),
            TextInput::make('survey_url')->label('Link de encuesta (Google Forms)')->url(),
            Select::make('status')->label('Estado')
                ->options(['active' => 'Activa', 'inactive' => 'Inactiva', 'finished' => 'Finalizada'])
                ->default('active')->required()
                ->helperText('Una sesión inactiva deja de verse para mentor, mentee y reportes.'),
            Toggle::make('visible_to_participant')->label('Visible para el mentee')->default(true),
            Toggle::make('visible_to_facilitator')->label('Visible para el mentor')->default(true),
            Toggle::make('is_locked')->label('Bloqueada')->default(false)->live()
                ->helperText('Si además es visible, se muestra como «Próximamente».'),
            DatePicker::make('unlock_at')->label('Se desbloquea el')->native(false)
                ->visible(fn (Get $get) => (bool) $get('is_locked')),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->heading('Sesiones adicionales de esta dupla')
            ->description('Sesiones extra que aplican solo a esta dupla, además del programa.')
            // Program order, never id or creation date.
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('number')->label('#')->badge()->sortable(),
                TextColumn::make('name')->label('Nombre')
                    ->state(fn (Session $s) => $s->getTranslation('name', $locale)),
                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('end_date')->label('Cierre')->date('d/m/Y')->placeholder('—'),
                IconColumn::make('visible_to_participant')->label('Mentee')->boolean(),
                IconColumn::make('visible_to_facilitator')->label('Mentor')->boolean(),
                TextColumn::make('access')->label('Acceso')->badge()
                    ->state(fn (Session $s) => $s->isLocked() ? 'Bloqueada' : 'Abierta')
                    ->color(fn (string $state) => $state === 'Bloqueada' ? 'warning' : 'success'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar sesión')
                    ->mutateDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['program_id'] = $owner->program_id;
                        $data['organization_id'] = $owner->organization_id;
                        $data['number'] = (int) ($owner->allSessions()->max('number') ?? 0) + 1;
                        $data['sort_order'] = (int) ($owner->allSessions()->max('sort_order') ?? 0) + 1;

                        return $data;
                    })
                    ->after(fn () => app(SessionRecordProvisioner::class)->forAssignment($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
