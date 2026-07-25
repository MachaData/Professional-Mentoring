<?php

namespace App\Filament\Resources\Assignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('facilitator.name')->label('Facilitador')->searchable()->sortable(),
                TextColumn::make('participant.name')->label('Participante')->searchable()->sortable(),
                TextColumn::make('program.name')->label('Programa')
                    ->getStateUsing(fn ($record) => $record->program?->getTranslation('name', app()->getLocale())),
                TextColumn::make('records_count')->label('Sesiones')->counts('records')->badge()->color('info'),
                TextColumn::make('records_completed')->label('Completadas')->badge()->color('success')
                    ->state(fn ($record) => $record->records()->where('status', 'completed')->count()),
                TextColumn::make('followups_count')->label('Seguimientos')->counts('followups')->badge()->color('gray'),
                TextColumn::make('followups_max_contacted_at')->label('Último contacto')
                    ->max('followups', 'contacted_at')->date('d/m/Y')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Activa', 'paused' => 'Pausada',
                        'finished' => 'Finalizada', 'cancelled' => 'Cancelada', default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'paused' => 'warning',
                        'finished' => 'info', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'active' => 'Activa', 'paused' => 'Pausada',
                    'finished' => 'Finalizada', 'cancelled' => 'Cancelada',
                ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver dupla'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
