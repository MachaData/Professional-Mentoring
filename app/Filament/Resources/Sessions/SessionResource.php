<?php

namespace App\Filament\Resources\Sessions;

use App\Filament\Resources\Sessions\Pages\CreateSession;
use App\Filament\Resources\Sessions\Pages\EditSession;
use App\Filament\Resources\Sessions\Pages\ListSessions;
use App\Filament\Resources\Sessions\RelationManagers\CustomFieldsRelationManager;
use App\Filament\Resources\Sessions\Schemas\SessionForm;
use App\Filament\Resources\Sessions\Tables\SessionsTable;
use App\Models\Session;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SessionResource extends Resource
{
    use \App\Filament\Concerns\ReadOnlyForCoordinator;

    protected static ?string $model = Session::class;

    /** Only the program curriculum here; per-dupla extras live under the dupla. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('assignment_id');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Sesiones';

    protected static ?string $modelLabel = 'sesión';

    protected static ?string $pluralModelLabel = 'sesiones';

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CustomFieldsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessions::route('/'),
            'create' => CreateSession::route('/create'),
            'edit' => EditSession::route('/{record}/edit'),
        ];
    }
}
