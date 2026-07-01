<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Session extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'description', 'objective'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'objective' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'requires_registration' => 'boolean',
            'visible_to_participant' => 'boolean',
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

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class)->orderBy('sort_order');
    }

    public function records(): HasMany
    {
        return $this->hasMany(SessionRecord::class);
    }

    /** A session is "open" when today falls within its window. */
    public function isOpen(): bool
    {
        $today = now()->startOfDay();

        return (! $this->start_date || $today->gte($this->start_date))
            && (! $this->end_date || $today->lte($this->end_date));
    }
}
