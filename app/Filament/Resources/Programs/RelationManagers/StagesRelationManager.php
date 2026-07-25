<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Models\Stage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    ->formatStateUsing(fn ($state) => $state === 'inactive' ? 'Inactiva' : 'Activa')
                    ->color(fn ($state) => $state === 'inactive' ? 'gray' : 'success'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) {
                        $data['organization_id'] = $this->getOwnerRecord()->organization_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                // Quick switch: deactivating a stage hides its sessions from the
                // mentor and mentee portals, from the coordinator's reports and
                // from the schedule — admins keep seeing them.
                Action::make('toggleStatus')
                    ->label(fn (Stage $record) => $record->isActive() ? 'Desactivar' : 'Activar')
                    ->icon(fn (Stage $record) => $record->isActive() ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Stage $record) => $record->isActive() ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Stage $record) => $record->isActive()
                        ? 'Las sesiones de esta etapa dejarán de verse para mentores, participantes y en los reportes.'
                        : 'Las sesiones de esta etapa volverán a verse para todos.')
                    ->action(fn (Stage $record) => $record
                        ->update(['status' => $record->isActive() ? 'inactive' : 'active'])),
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
