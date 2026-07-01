<?php

namespace App\Filament\Resources\Surveys\Tables;

use App\Support\ResourceOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SurveysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),
                TextColumn::make('program.name')->label('Programa')
                    ->getStateUsing(fn ($record) => $record->program?->getTranslation('name', app()->getLocale())),
                TextColumn::make('session.name')->label('Sesión')
                    ->getStateUsing(fn ($record) => $record->session?->getTranslation('name', app()->getLocale()))
                    ->placeholder('Programa'),
                TextColumn::make('visible_to')->label('Visible para')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => ResourceOptions::visibility()[$state] ?? $state),
                TextColumn::make('display_moment')->label('Momento')
                    ->formatStateUsing(fn ($state) => ResourceOptions::displayMoment()[$state] ?? $state),
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
