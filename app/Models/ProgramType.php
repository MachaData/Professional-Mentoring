<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProgramType extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = ['id'];

    // Not tenant-scoped: a null organization_id means a global catalog type.
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public array $translatable = [
        'name', 'description', 'facilitator_label', 'participant_label',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'facilitator_label' => 'array',
            'participant_label' => 'array',
        ];
    }
}
