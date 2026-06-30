<?php

namespace App\Filament\Resources\ProgramTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),
                TextColumn::make('facilitator_label')->label('Facilitador')
                    ->getStateUsing(fn ($record) => $record->getTranslation('facilitator_label', app()->getLocale()))
                    ->badge()->color('info'),
                TextColumn::make('participant_label')->label('Participante')
                    ->getStateUsing(fn ($record) => $record->getTranslation('participant_label', app()->getLocale()))
                    ->badge()->color('gray'),
                TextColumn::make('organization.name')->label('Organización')
                    ->placeholder('Global'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                //
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
