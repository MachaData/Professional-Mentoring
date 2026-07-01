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
}
