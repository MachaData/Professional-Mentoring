<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WelcomePopupsRelationManager extends RelationManager
{
    protected static string $relationship = 'welcomePopups';

    protected static ?string $title = 'Popups de bienvenida';

    protected static ?string $modelLabel = 'popup';

    protected static ?string $pluralModelLabel = 'popups de bienvenida';

    /** Portal roles that see the welcome popup. */
    protected static array $roleOptions = [
        User::ROLE_PARTICIPANT => 'Mentee (participante)',
        User::ROLE_FACILITATOR => 'Mentor (facilitador)',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('role')->label('Rol')
                        ->options(self::$roleOptions)->required()
                        ->helperText('A quién se le muestra este popup dentro del programa.'),
                    Toggle::make('enabled')->label('Activo')->default(true)
                        ->helperText('Si está desactivado, no se muestra.'),
                    TextInput::make('title.es')->label('Título (ES)'),
                    TextInput::make('title.en')->label('Título (EN)'),
                    Textarea::make('body.es')->label('Mensaje (ES)')->rows(3),
                    Textarea::make('body.en')->label('Mensaje (EN)')->rows(3),
                    TextInput::make('video_url')->label('Link de video (YouTube / Vimeo)')
                        ->url()->placeholder('https://www.youtube.com/watch?v=…')
                        ->columnSpanFull()
                        ->helperText('Opcional. Se muestra incrustado dentro del popup.'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('role')->label('Rol')->badge()
                    ->formatStateUsing(fn ($state) => self::$roleOptions[$state] ?? $state),
                IconColumn::make('enabled')->label('Activo')->boolean(),
                TextColumn::make('title')->label('Título')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', app()->getLocale(), false))
                    ->placeholder('—'),
                IconColumn::make('video_url')->label('Video')->boolean()
                    ->getStateUsing(fn ($record) => filled($record->video_url)),
                TextColumn::make('updated_at')->label('Actualizado')->since(),
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
