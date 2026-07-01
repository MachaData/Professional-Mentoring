<?php

namespace App\Filament\Resources\FormTemplates\RelationManagers;

use App\Filament\Shared\CustomFieldSchema;
use App\Filament\Shared\FieldsRelationManagerTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'Campos del formulario';

    public function form(Schema $schema): Schema
    {
        return $schema->components(CustomFieldSchema::formComponents());
    }

    public function table(Table $table): Table
    {
        return FieldsRelationManagerTable::configure($table, $this)->heading('Campos');
    }
}
