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

    protected static function booted(): void
    {
        // Keep sort_order aligned with the session number so every list shows
        // Sesión 1, 2, 3… A sort_order of 0 means "unset" (the column default),
        // e.g. sessions created from the admin form which has no order field.
        static::saving(function (self $session) {
            if (empty($session->sort_order) && ! empty($session->number)) {
                $session->sort_order = $session->number;
            }
        });
    }

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

    /** Set only for extra sessions that belong to a single dupla. */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** Program-wide curriculum sessions (shared by every dupla). */
    public function scopeProgramWide($query)
    {
        return $query->whereNull('assignment_id');
    }

    /** True when this session is an extra added for a specific dupla. */
    public function isExtra(): bool
    {
        return $this->assignment_id !== null;
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /** The form template last applied to seed this session's fields (optional). */
    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
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
