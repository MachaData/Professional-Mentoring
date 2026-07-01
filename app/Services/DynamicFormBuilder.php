<?php

namespace App\Services;

use App\Enums\FieldType;
use App\Models\CustomField;
use App\Models\SessionRecord;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;

/**
 * Turns a collection of CustomField definitions into Filament form components,
 * and maps submitted state to/from typed session_record_values rows.
 *
 * This is the single source of truth for how a dynamic field behaves, reused by
 * the admin panel and (later) the facilitator portal.
 */
class DynamicFormBuilder
{
    /**
     * @param  Collection<int,CustomField>  $fields
     * @return array<int,\Filament\Schemas\Components\Component>
     */
    public function components(Collection $fields): array
    {
        return $fields
            ->where('status', 'active')
            ->map(fn (CustomField $field) => $this->component($field))
            ->all();
    }

    protected function component(CustomField $field)
    {
        $locale = app()->getLocale();
        $key = "field_{$field->id}";
        $label = $field->getTranslation('label', $locale, false) ?: $field->name;
        $placeholder = $field->getTranslation('placeholder', $locale, false) ?: null;
        $help = $field->getTranslation('help_text', $locale, false) ?: null;

        $component = match ($field->field_type) {
            FieldType::Textarea => Textarea::make($key)->rows(3),
            FieldType::Date => DatePicker::make($key),
            FieldType::Time => TimePicker::make($key),
            FieldType::Number => TextInput::make($key)->numeric(),
            FieldType::Rating => Select::make($key)->options(array_combine(range(1, 5), range(1, 5))),
            FieldType::Url => TextInput::make($key)->url(),
            FieldType::Select => Select::make($key)->options($this->options($field)),
            FieldType::Multiselect => Select::make($key)->multiple()->options($this->options($field)),
            FieldType::Radio => Radio::make($key)->options($this->options($field)),
            FieldType::Checkbox => CheckboxList::make($key)->options($this->options($field)),
            FieldType::File => FileUpload::make($key)->directory('session-records'),
            FieldType::Heading => Placeholder::make($key)->content($label)->label(''),
            FieldType::Separator => Placeholder::make($key)->label('')->content('———'),
            FieldType::Readonly => Placeholder::make($key)->content($field->default_value ?? $label),
            default => TextInput::make($key),
        };

        if (method_exists($component, 'label')) {
            $component->label($label);
        }

        if (! $field->field_type->isLayout()) {
            if ($field->is_required) {
                $component->required();
            }
            if ($placeholder && method_exists($component, 'placeholder')) {
                $component->placeholder($placeholder);
            }
            if ($help && method_exists($component, 'helperText')) {
                $component->helperText($help);
            }
            if ($field->default_value !== null && method_exists($component, 'default')) {
                $component->default($field->default_value);
            }
        }

        return $component;
    }

    /** @return array<string,string> */
    protected function options(CustomField $field): array
    {
        $locale = app()->getLocale();
        $options = $field->options_json ?? [];
        $result = [];

        foreach ($options as $key => $option) {
            if (is_array($option)) {
                // ["value" => "x", "label" => ["es"=>..,"en"=>..]]
                $value = $option['value'] ?? $key;
                $label = is_array($option['label'] ?? null)
                    ? ($option['label'][$locale] ?? reset($option['label']))
                    : ($option['label'] ?? $value);
                $result[$value] = $label;
            } elseif (is_string($key)) {
                // Associative {value: label} map (from the KeyValue builder).
                $result[$key] = $option;
            } else {
                // Plain list of strings.
                $result[$option] = $option;
            }
        }

        return $result;
    }

    /**
     * Hydrate form state (field_{id} => value) from a saved record.
     *
     * @return array<string,mixed>
     */
    public function stateFromRecord(SessionRecord $record): array
    {
        $state = [];
        foreach ($record->values()->with('customField')->get() as $value) {
            $field = $value->customField;
            if (! $field) {
                continue;
            }
            $state["field_{$field->id}"] = $value->{$field->field_type->valueColumn()};
        }

        return $state;
    }

    /**
     * Persist submitted form state into typed session_record_values rows.
     *
     * @param  Collection<int,CustomField>  $fields
     * @param  array<string,mixed>  $state
     */
    public function saveValues(SessionRecord $record, Collection $fields, array $state): void
    {
        foreach ($fields as $field) {
            if ($field->field_type->isLayout()) {
                continue;
            }

            $key = "field_{$field->id}";
            if (! array_key_exists($key, $state)) {
                continue;
            }

            $record->values()->updateOrCreate(
                ['custom_field_id' => $field->id],
                [$field->field_type->valueColumn() => $state[$key]],
            );
        }
    }
}
