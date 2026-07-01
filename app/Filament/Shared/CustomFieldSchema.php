<?php

namespace App\Filament\Shared;

use App\Enums\FieldType;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

/**
 * Reusable form + table config for editing a CustomField definition, shared by
 * the form-template builder and per-session field managers.
 */
class CustomFieldSchema
{
    /** @return array<int,mixed> */
    public static function formComponents(): array
    {
        return [
            Section::make('Campo')
                ->columns(2)
                ->schema([
                    TextInput::make('label.es')->label('Etiqueta (ES)')->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            if (blank($get('name'))) {
                                $set('name', Str::slug((string) $state, '_'));
                            }
                        }),
                    TextInput::make('label.en')->label('Label (EN)')->required(),
                    TextInput::make('name')->label('Clave interna')->required()
                        ->helperText('Identificador técnico único dentro del formulario.'),
                    Select::make('field_type')->label('Tipo de campo')
                        ->options(FieldType::options())
                        ->default(FieldType::Text->value)
                        ->required()
                        ->live(),
                    TextInput::make('placeholder.es')->label('Placeholder (ES)'),
                    TextInput::make('placeholder.en')->label('Placeholder (EN)'),
                    Textarea::make('help_text.es')->label('Ayuda (ES)')->rows(2),
                    Textarea::make('help_text.en')->label('Help (EN)')->rows(2),
                ]),

            Section::make('Opciones')
                ->visible(fn (Get $get) => in_array($get('field_type'), [
                    FieldType::Select->value, FieldType::Radio->value,
                    FieldType::Checkbox->value, FieldType::Multiselect->value,
                ], true))
                ->schema([
                    KeyValue::make('options_json')
                        ->label('Opciones')
                        ->keyLabel('Valor')
                        ->valueLabel('Etiqueta')
                        ->addActionLabel('Agregar opción'),
                ]),

            Section::make('Comportamiento')
                ->columns(2)
                ->schema([
                    Toggle::make('is_required')->label('Obligatorio'),
                    Toggle::make('is_visible_to_participant')->label('Visible para participante'),
                    Toggle::make('is_internal')->label('Interno (solo facilitador/admin)'),
                    Select::make('status')->label('Estado')
                        ->options(['active' => 'Activo', 'inactive' => 'Inactivo'])
                        ->default('active')->required(),
                ]),
        ];
    }
}
