<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\SessionRecord;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only view of the session records a facilitator has logged for this dupla.
 * Lets coordinators and admins review progress and the registered content.
 */
class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = 'Sesiones registradas';

    /** Supervisors (admins + coordinators) may fill in each session's data. */
    public function isReadOnly(): bool
    {
        return ! (auth()->user()?->canSuperviseDuplas() ?? false);
    }

    /** Operational fields a coordinator/admin fills per session (not the survey). */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->label('Registro')
                ->options([
                    SessionRecord::STATUS_PENDING => 'Pendiente',
                    SessionRecord::STATUS_DRAFT => 'Borrador',
                    SessionRecord::STATUS_COMPLETED => 'Completada',
                ])->required(),
            Select::make('attendance')->label('Asistencia')
                ->options(collect(SessionRecord::ATTENDANCE)->map(fn ($l) => __($l))->all()),
            Select::make('modality')->label('Modalidad')
                ->options(collect(SessionRecord::MODALITY)->map(fn ($l) => __($l))->all()),
            DatePicker::make('real_session_date')->label('Fecha real de la sesión')->native(false),
            TextInput::make('meeting_url')->label('Link de la reunión (Meet/Zoom/Teams)')->url(),
        ])->columns(2);
    }

    public function infolist(Schema $schema): Schema
    {
        $locale = app()->getLocale();

        return $schema->components([
            TextEntry::make('session.name')->label('Sesión')
                ->state(fn (SessionRecord $r) => $r->session?->getTranslation('name', $locale)),
            TextEntry::make('attendance')->label('Asistencia')->badge()
                ->formatStateUsing(fn ($state) => __(SessionRecord::ATTENDANCE[$state] ?? $state)),
            TextEntry::make('modality')->label('Modalidad')
                ->formatStateUsing(fn ($state) => $state ? __(SessionRecord::MODALITY[$state] ?? $state) : '—'),
            TextEntry::make('real_session_date')->label('Fecha real')->date('d/m/Y')->placeholder('—'),
            TextEntry::make('meeting_url')->label('Link de la reunión')->placeholder('—')->url(fn ($state) => $state, true),
            RepeatableEntry::make('values')->label('Respuestas registradas')
                ->schema([
                    TextEntry::make('customField.label')->label('')
                        ->state(fn ($record) => $record->customField?->getTranslation('label', $locale)),
                    TextEntry::make('value')->label('')
                        ->state(fn ($record) => $record->value_text
                            ?? $record->value_date?->format('d/m/Y')
                            ?? $record->value_number
                            ?? (is_array($record->value_json) ? implode(', ', $record->value_json) : null)),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->heading('Sesiones registradas')
            ->defaultSort('session_id')
            ->columns([
                TextColumn::make('session.number')->label('#')->badge()
                    ->state(fn (SessionRecord $r) => $r->session?->number),
                TextColumn::make('session.name')->label('Sesión')
                    ->state(fn (SessionRecord $r) => $r->session?->getTranslation('name', $locale)),
                TextColumn::make('status')->label('Registro')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => 'Completada', 'draft' => 'Borrador',
                        'pending' => 'Pendiente', default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success', 'draft' => 'warning', default => 'gray',
                    }),
                TextColumn::make('attendance')->label('Asistencia')->badge()
                    ->formatStateUsing(fn ($state) => __(SessionRecord::ATTENDANCE[$state] ?? $state))
                    ->color(fn ($state) => match ($state) {
                        'attended' => 'success', 'absent' => 'danger', 'rescheduled' => 'warning', default => 'gray',
                    }),
                TextColumn::make('modality')->label('Modalidad')
                    ->formatStateUsing(fn ($state) => $state ? __(SessionRecord::MODALITY[$state] ?? $state) : '—'),
                TextColumn::make('real_session_date')->label('Fecha real')->date('d/m/Y')->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar')
                    ->visible(fn () => auth()->user()?->canSuperviseDuplas() ?? false),
            ]);
    }
}
