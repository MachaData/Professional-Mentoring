<?php

namespace App\Filament\Resources\Reminders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RemindersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('program.name')->label('Programa')
                    ->getStateUsing(fn ($record) => $record->program?->getTranslation('name', app()->getLocale())),
                TextColumn::make('recipient_type')->label('Destinatario')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'facilitator' => 'Facilitador', 'participant' => 'Participante',
                        'both' => 'Ambos', 'admin' => 'Admin', default => $state,
                    }),
                TextColumn::make('schedule')->label('Programación')
                    ->state(fn ($record) => trim(sprintf('%d días %s %s',
                        $record->days,
                        $record->timing === 'before' ? 'antes del' : ($record->timing === 'after' ? 'después del' : 'el'),
                        $record->anchor === 'session_start' ? 'inicio' : 'fin',
                    ))),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
