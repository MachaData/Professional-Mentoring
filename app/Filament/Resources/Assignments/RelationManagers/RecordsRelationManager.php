<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\SessionRecord;
use Filament\Actions\ViewAction;
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

    public function isReadOnly(): bool
    {
        return true;
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
            ]);
    }
}
