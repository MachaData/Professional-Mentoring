<?php

namespace App\Filament\Resources\Programs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Programa')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                    ->searchable(query: fn ($query, $search) => $query->whereRaw("name->>'es' ILIKE ?", ["%{$search}%"]))
                    ->sortable(),
                TextColumn::make('client.name')->label('Cliente')->placeholder('—'),
                TextColumn::make('stages_count')->label('Etapas')->counts('stages')->badge()->color('gray'),
                TextColumn::make('sessions_count')->label('Sesiones')->counts('sessions')->badge()->color('info'),
                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y')->sortable(),
                TextColumn::make('end_date')->label('Fin')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'Borrador', 'active' => 'Activo', 'paused' => 'Pausado',
                        'finished' => 'Finalizado', 'archived' => 'Archivado', default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success', 'draft' => 'gray', 'paused' => 'warning',
                        'finished' => 'info', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'draft' => 'Borrador', 'active' => 'Activo', 'paused' => 'Pausado',
                    'finished' => 'Finalizado', 'archived' => 'Archivado',
                ]),
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
