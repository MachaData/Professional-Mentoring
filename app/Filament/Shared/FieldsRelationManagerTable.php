<?php

namespace App\Filament\Shared;

use App\Enums\FieldType;
use App\Models\FormTemplate;
use App\Models\Session;
use App\Services\FormTemplateApplier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Shared table config for managing CustomField rows in a relation manager.
 * Fills organization_id from the owner record on create.
 */
class FieldsRelationManagerTable
{
    public static function configure(Table $table, RelationManager $manager): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->badge(),
                TextColumn::make('label')->label('Etiqueta')
                    ->getStateUsing(fn ($record) => $record->getTranslation('label', app()->getLocale())),
                TextColumn::make('name')->label('Clave')->color('gray'),
                TextColumn::make('field_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (FieldType $state) => FieldType::options()[$state->value] ?? $state->value),
                IconColumn::make('is_required')->label('Oblig.')->boolean(),
                IconColumn::make('is_visible_to_participant')->label('Visible')->boolean(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) use ($manager) {
                        $owner = $manager->getOwnerRecord();
                        $data['organization_id'] = $owner->organization_id;

                        return $data;
                    }),
                Action::make('applyFormTemplate')
                    ->label('Aplicar plantilla')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->visible(fn () => $manager->getOwnerRecord() instanceof Session)
                    ->schema([
                        Select::make('form_template_id')->label('Plantilla de formulario')
                            ->options(fn () => FormTemplate::query()
                                ->where('status', 'active')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()->required(),
                        Toggle::make('replace')->label('Reemplazar campos existentes')
                            ->helperText('Si se activa, se eliminan los campos actuales de la sesión antes de copiar. Si no, se agregan al final.'),
                    ])
                    ->action(function (array $data) use ($manager) {
                        $template = FormTemplate::findOrFail($data['form_template_id']);
                        $count = app(FormTemplateApplier::class)->apply(
                            $template,
                            $manager->getOwnerRecord(),
                            (bool) ($data['replace'] ?? false),
                        );

                        Notification::make()
                            ->success()
                            ->title('Plantilla aplicada')
                            ->body("Se copiaron {$count} campos desde «{$template->name}».")
                            ->send();
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
