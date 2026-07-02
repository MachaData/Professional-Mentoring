<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\SharedFile;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Read-only view of files/links/notes shared within the dupla. */
class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Archivos compartidos';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Archivos compartidos')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => SharedFile::TYPES[$state] ?? $state),
                TextColumn::make('uploader.name')->label('Subido por'),
                TextColumn::make('session.name')->label('Sesión')
                    ->getStateUsing(fn (SharedFile $r) => $r->session?->getTranslation('name', app()->getLocale()))
                    ->placeholder('General'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('open')->label('Abrir')
                    ->url(fn (SharedFile $r) => $r->url(), true)
                    ->visible(fn (SharedFile $r) => filled($r->url())),
            ]);
    }
}
