<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Read-only view of the dupla's mailbox for supervision. */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Buzón de mensajes';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Buzón de mensajes')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sender.name')->label('De'),
                TextColumn::make('body')->label('Mensaje')->limit(60)->wrap()->placeholder('(adjunto)'),
                TextColumn::make('attachment_name')->label('Adjunto')->placeholder('—'),
                IconColumn::make('read_at')->label('Leído')->boolean(),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ]);
    }
}
