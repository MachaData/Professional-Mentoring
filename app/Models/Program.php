<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Program extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations, SoftDeletes;

    protected $guarded = ['id'];

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
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function programType(): BelongsTo
    {
        return $this->belongsTo(ProgramType::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('sort_order');
    }

    /** Program-wide curriculum sessions (excludes per-dupla extras). */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class)->whereNull('assignment_id')->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function welcomePopups(): HasMany
    {
        return $this->hasMany(WelcomePopup::class);
    }

    /**
     * Visible facilitator label: program override → program type → fallback.
     */
    public function facilitatorLabel(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->getTranslation('facilitator_label', $locale, false)
            ?: ($this->programType?->getTranslation('facilitator_label', $locale, false) ?: 'Facilitador');
    }

    public function participantLabel(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->getTranslation('participant_label', $locale, false)
            ?: ($this->programType?->getTranslation('participant_label', $locale, false) ?: 'Participante');
    }
}
