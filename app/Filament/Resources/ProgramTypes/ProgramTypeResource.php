<?php

namespace App\Filament\Resources\ProgramTypes;

use App\Filament\Resources\ProgramTypes\Pages\CreateProgramType;
use App\Filament\Resources\ProgramTypes\Pages\EditProgramType;
use App\Filament\Resources\ProgramTypes\Pages\ListProgramTypes;
use App\Filament\Resources\ProgramTypes\Schemas\ProgramTypeForm;
use App\Filament\Resources\ProgramTypes\Tables\ProgramTypesTable;
use App\Models\ProgramType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProgramTypeResource extends Resource
{
    use \App\Filament\Concerns\HiddenFromCoordinator;

    protected static ?string $model = ProgramType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Tipos de programa';

    protected static ?string $modelLabel = 'tipo de programa';

    protected static ?string $pluralModelLabel = 'tipos de programa';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Org-admins see global catalog (null org) + their own custom types.
        if ($user && ! $user->isSuperadmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', $user->organization_id);
            });
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ProgramTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramTypes::route('/'),
            'create' => CreateProgramType::route('/create'),
            'edit' => EditProgramType::route('/{record}/edit'),
        ];
    }
}
