<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class CustomField extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['label', 'placeholder', 'help_text'];

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'placeholder' => 'array',
            'help_text' => 'array',
            'options_json' => 'array',
            'is_required' => 'boolean',
            'is_visible_to_participant' => 'boolean',
            'is_internal' => 'boolean',
            'field_type' => FieldType::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    /**
     * Options as a value => label map for the current locale, supporting both a
     * plain {value: label} map and a list of ["value","label"] entries.
     *
     * @return array<string,string>
     */
    public function resolvedOptions(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $result = [];

        foreach ($this->options_json ?? [] as $key => $option) {
            if (is_array($option)) {
                $value = $option['value'] ?? $key;
                $label = is_array($option['label'] ?? null)
                    ? ($option['label'][$locale] ?? reset($option['label']))
                    : ($option['label'] ?? $value);
                $result[$value] = $label;
            } elseif (is_string($key)) {
                $result[$key] = $option;
            } else {
                $result[$option] = $option;
            }
        }

        return $result;
    }
}
