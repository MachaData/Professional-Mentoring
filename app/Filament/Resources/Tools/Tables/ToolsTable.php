<?php

namespace App\Filament\Resources\Tools\Tables;

use App\Support\ResourceOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('business_unit')->label('Unidad de negocio')->badge()->color('gray')
                    ->placeholder('Todas'),
                TextColumn::make('language')->label('Idioma')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => ResourceOptions::languageLabel($state))
                    ->placeholder('Sin idioma'),
                TextColumn::make('relations_count')->label('Asociaciones')->counts('relations')->badge()->color('info'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(ResourceOptions::toolTypes()),
                SelectFilter::make('business_unit')->label('Unidad de negocio')
                    ->options(fn () => ResourceOptions::businessUnits()),
                SelectFilter::make('language')->label('Idioma')
                    ->options(ResourceOptions::languages()),
                SelectFilter::make('visibility')->label('Rol')
                    ->options(ResourceOptions::visibility()),
                // "Ubicación" is not a column: it follows from the associations,
                // same rule the portal uses — a row with a stage or a session
                // belongs inside that session, never in the general list.
                SelectFilter::make('placement')->label('Ubicación')
                    ->options(ResourceOptions::placements())
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'general' => $query->whereHas('relations', fn ($q) => $q->whereNotNull('program_id')
                            ->whereNull('stage_id')->whereNull('session_id')),
                        'stage' => $query->whereHas('relations', fn ($q) => $q->whereNotNull('stage_id')),
                        'session' => $query->whereHas('relations', fn ($q) => $q->whereNotNull('session_id')),
                        default => $query,
                    }),
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
