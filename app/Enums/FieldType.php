<?php

namespace App\Enums;

/**
 * Supported dynamic field types. Drives both the admin builder and the
 * dynamic form renderer. Adding a type here + a case in DynamicFormBuilder
 * is all that's needed to extend the platform.
 */
enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Date = 'date';
    case Time = 'time';
    case Number = 'number';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Multiselect = 'multiselect';
    case Rating = 'rating';
    case File = 'file';
    case Url = 'url';
    case Heading = 'heading';
    case Separator = 'separator';
    case Readonly = 'readonly';

    /** Field types that require a configured list of options. */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox, self::Multiselect], true);
    }

    /** Presentational-only types that never store a value. */
    public function isLayout(): bool
    {
        return in_array($this, [self::Heading, self::Separator], true);
    }

    /** Which session_record_values column stores this type. */
    public function valueColumn(): string
    {
        return match ($this) {
            self::Number, self::Rating => 'value_number',
            self::Date => 'value_date',
            self::File => 'value_file',
            self::Checkbox, self::Multiselect => 'value_json',
            default => 'value_text',
        };
    }

    /** @return array<string,string> value => label for admin selects */
    public static function options(): array
    {
        return [
            self::Text->value => 'Texto corto',
            self::Textarea->value => 'Texto largo',
            self::Date->value => 'Fecha',
            self::Time->value => 'Hora',
            self::Number->value => 'Número',
            self::Select->value => 'Lista desplegable',
            self::Radio->value => 'Opción única',
            self::Checkbox->value => 'Casillas (múltiple)',
            self::Multiselect->value => 'Selección múltiple',
            self::Rating->value => 'Valoración (rating)',
            self::File->value => 'Archivo',
            self::Url->value => 'Enlace (URL)',
            self::Heading->value => 'Título (solo lectura)',
            self::Separator->value => 'Separador',
            self::Readonly->value => 'Texto informativo',
        ];
    }
}
