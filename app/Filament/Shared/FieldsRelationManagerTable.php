<?php

namespace App\Filament\Shared;

use App\Enums\FieldType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Shared table config for managing CustomField rows in a relation manager.
 * Fills organization_id from the owner record on create.
 */
class FieldsRelationManagerTable
{
    public static function configure(Table $table, RelationManager $manager): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->badge(),
                TextColumn::make('label')->label('Etiqueta')
                    ->getStateUsing(fn ($record) => $record->getTranslation('label', app()->getLocale())),
                TextColumn::make('name')->label('Clave')->color('gray'),
                TextColumn::make('field_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (FieldType $state) => FieldType::options()[$state->value] ?? $state->value),
                IconColumn::make('is_required')->label('Oblig.')->boolean(),
                IconColumn::make('is_visible_to_participant')->label('Visible')->boolean(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) use ($manager) {
                        $owner = $manager->getOwnerRecord();
                        $data['organization_id'] = $owner->organization_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
