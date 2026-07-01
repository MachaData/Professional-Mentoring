<?php

namespace App\Filament\Resources\Sessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('number')->label('#')->badge()->sortable(),
                TextColumn::make('name')->label('Sesión')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),
                TextColumn::make('program.name')->label('Programa')
                    ->getStateUsing(fn ($record) => $record->program?->getTranslation('name', app()->getLocale()))
                    ->toggleable(),
                TextColumn::make('stage.name')->label('Etapa')
                    ->getStateUsing(fn ($record) => $record->stage?->getTranslation('name', app()->getLocale()))
                    ->badge()->color('gray')->placeholder('—'),
                TextColumn::make('custom_fields_count')->label('Campos')->counts('customFields')->badge()->color('info'),
                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y'),
                TextColumn::make('end_date')->label('Fin')->date('d/m/Y'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'finished' => 'info', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('program_id')->label('Programa')
                    ->relationship('program', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale())),
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
