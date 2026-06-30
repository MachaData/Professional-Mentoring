<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';

    protected static ?string $title = 'Etapas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name.es')->label('Nombre (ES)')->required(),
                TextInput::make('name.en')->label('Name (EN)')->required(),
                Textarea::make('description.es')->label('Descripción (ES)')->rows(2),
                Textarea::make('description.en')->label('Description (EN)')->rows(2),
                ColorPicker::make('color')->label('Color'),
                Select::make('status')->label('Estado')
                    ->options(['active' => 'Activa', 'inactive' => 'Inactiva'])
                    ->default('active')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Etapas')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->badge(),
                TextColumn::make('name')->label('Nombre')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale())),
                ColorColumn::make('color')->label('Color'),
                TextColumn::make('sessions_count')->label('Sesiones')->counts('sessions')->badge()->color('info'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) {
                        $data['organization_id'] = $this->getOwnerRecord()->organization_id;

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
