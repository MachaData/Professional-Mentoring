<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\Message;
use App\Models\Session;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only view of the dupla's mailbox for supervision: coordinators and admins
 * open each message in full (subject, body, attachment, related session) without
 * ever being able to write, edit or delete in someone else's conversation.
 */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Buzón de mensajes';

    /** Private mentor↔mentee conversations are hidden from read-only clients. */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ! (auth()->user()?->isClient() ?? false);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    /** Sessions of this dupla, for the "related session" filter. */
    protected function sessionOptions(): array
    {
        return $this->getOwnerRecord()->allSessions()
            ->mapWithKeys(fn (Session $s) => [
                $s->id => 'S'.$s->number.' · '.$s->getTranslation('name', app()->getLocale()),
            ])
            ->all();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('sender.name')->label('De'),
            TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            TextEntry::make('subject')->label('Asunto')->placeholder('(sin asunto)'),
            TextEntry::make('session.name')->label('Sesión relacionada')
                ->getStateUsing(fn (Message $r) => $r->session
                    ? 'S'.$r->session->number.' · '.$r->session->getTranslation('name', app()->getLocale())
                    : null)
                ->placeholder('Mensaje general'),
            TextEntry::make('read_at')->label('Leído')->dateTime('d/m/Y H:i')->placeholder('No leído'),
            TextEntry::make('body')->label('Mensaje')->placeholder('—')->columnSpanFull(),
            TextEntry::make('attachment_name')->label('Adjunto')->placeholder('Sin adjunto')
                ->url(fn (Message $r) => $r->attachmentUrl(), true)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Buzón de mensajes')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sender.name')->label('De'),
                TextColumn::make('subject')->label('Asunto')->limit(40)->placeholder('(sin asunto)'),
                TextColumn::make('body')->label('Mensaje')->limit(60)->wrap()->placeholder('(adjunto)'),
                TextColumn::make('session.name')->label('Sesión')
                    ->getStateUsing(fn (Message $r) => $r->session
                        ? 'S'.$r->session->number
                        : null)
                    ->badge()->color('gray')->placeholder('General'),
                TextColumn::make('attachment_name')->label('Adjunto')->placeholder('—'),
                IconColumn::make('read_at')->label('Leído')->boolean(),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                TernaryFilter::make('read_at')->label('Leído')
                    ->nullable()
                    ->trueLabel('Leídos')->falseLabel('No leídos')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('read_at'),
                        false: fn ($q) => $q->whereNull('read_at'),
                        blank: fn ($q) => $q,
                    ),
                SelectFilter::make('session_id')->label('Sesión')
                    ->options(fn () => $this->sessionOptions()),
            ])
            ->recordActions([
                ViewAction::make()->label('Leer'),
                Action::make('attachment')->label('Adjunto')->icon('heroicon-o-paper-clip')
                    ->url(fn (Message $r) => $r->attachmentUrl(), true)
                    ->visible(fn (Message $r) => filled($r->attachmentUrl())),
            ]);
    }
}
