<?php

namespace App\Filament\Resources\FormTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('program.name')->label('Programa')
                    ->getStateUsing(fn ($record) => $record->program?->getTranslation('name', app()->getLocale()))
                    ->placeholder('Todos'),
                TextColumn::make('fields_count')->label('Campos')->counts('fields')->badge()->color('info'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('updated_at')->label('Actualizado')->since()->sortable(),
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
