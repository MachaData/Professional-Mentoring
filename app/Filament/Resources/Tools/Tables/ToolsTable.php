<?php

namespace App\Filament\Resources\Tools\Tables;

use App\Support\ResourceOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ToolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                    ->searchable(query: fn ($q, $s) => $q->whereRaw("name->>'es' ILIKE ?", ["%{$s}%"])),
                TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => ResourceOptions::toolTypes()[$state] ?? $state),
                TextColumn::make('visibility')->label('Visibilidad')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => ResourceOptions::visibility()[$state] ?? $state),
                TextColumn::make('relations_count')->label('Asociaciones')->counts('relations')->badge()->color('info'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(ResourceOptions::toolTypes()),
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
