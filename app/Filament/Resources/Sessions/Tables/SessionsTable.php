<?php

namespace App\Filament\Resources\Sessions\Tables;

use App\Models\Session;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                IconColumn::make('visible_to_participant')->label('Mentee')->boolean()->toggleable(),
                IconColumn::make('visible_to_facilitator')->label('Mentor')->boolean()->toggleable(),
                // Reflects the effective state, so a session past its unlock_at
                // reads "Abierta" even while is_locked is still set.
                TextColumn::make('access')->label('Acceso')->badge()
                    ->getStateUsing(fn (Session $record) => $record->isLocked() ? 'Bloqueada' : 'Abierta')
                    ->color(fn (string $state) => $state === 'Bloqueada' ? 'warning' : 'success')
                    ->description(fn (Session $record) => $record->isLocked() && $record->unlock_at
                        ? 'Se abre el '.$record->unlock_at->format('d/m/Y')
                        : null),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Activa', 'finished' => 'Finalizada', default => 'Inactiva',
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'finished' => 'info', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('program_id')->label('Programa')
                    ->relationship('program', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale())),
                SelectFilter::make('status')->label('Estado')
                    ->options(['active' => 'Activa', 'inactive' => 'Inactiva', 'finished' => 'Finalizada']),
                TernaryFilter::make('is_locked')->label('Bloqueada'),
                TernaryFilter::make('visible_to_participant')->label('Visible para el mentee'),
                TernaryFilter::make('visible_to_facilitator')->label('Visible para el mentor'),
            ])
            ->recordActions([
                // Activation is the "exists for the audience" switch; the lock
                // below is the "not yet" one. Deactivating hides the session
                // from mentor, mentee, coordinator and reports — never for admins,
                // and it deletes nothing.
                Action::make('toggleStatus')
                    ->label(fn (Session $record) => $record->status === 'inactive' ? 'Activar' : 'Desactivar')
                    ->icon(fn (Session $record) => $record->status === 'inactive' ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn (Session $record) => $record->status === 'inactive' ? 'success' : 'gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Session $record) => $record->status === 'inactive'
                        ? 'La sesión volverá a verse para mentores, participantes y en los reportes.'
                        : 'La sesión dejará de verse para mentores, participantes y en los reportes. No se borra nada.')
                    ->visible(fn () => auth()->user()?->canManageContent() ?? false)
                    ->action(fn (Session $record) => $record->forceFill([
                        'status' => $record->status === 'inactive' ? 'active' : 'inactive',
                    ])->save()),
                // Manual unlock/lock, the counterpart to the unlock_at date.
                Action::make('toggleLock')
                    ->label(fn (Session $record) => $record->is_locked ? 'Desbloquear' : 'Bloquear')
                    ->icon(fn (Session $record) => $record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (Session $record) => $record->is_locked ? 'success' : 'gray')
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()?->canManageContent() ?? false)
                    // Clearing unlock_at on unlock keeps a stale date from
                    // silently re-arming nothing, and reads cleanly in the form.
                    ->action(fn (Session $record) => $record->forceFill([
                        'is_locked' => ! $record->is_locked,
                        'unlock_at' => $record->is_locked ? null : $record->unlock_at,
                    ])->save()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
