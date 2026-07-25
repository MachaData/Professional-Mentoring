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
            'unlock_at' => 'date',
            'requires_registration' => 'boolean',
            'visible_to_participant' => 'boolean',
            'visible_to_facilitator' => 'boolean',
            'is_locked' => 'boolean',
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

    /**
     * Sessions a non-admin audience may see: the session itself is not
     * deactivated *and* its stage (if any) is active. Deactivating either one
     * takes the session out of the portals, the reports and the schedule, while
     * admins query without this scope and still see everything.
     *
     * Note 'finished' is not 'inactive': a closed session stays on the record.
     */
    public function scopeAvailableToAudience($query)
    {
        return $query
            ->where('status', '!=', 'inactive')
            ->where(function ($q) {
                $q->whereNull('stage_id')
                    ->orWhereHas('stage', fn ($s) => $s->where('status', '!=', 'inactive'));
            });
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

    // ---- Progressive access --------------------------------------------

    /**
     * A locked session cannot be opened by mentor or mentee. It stays locked
     * until an admin turns the flag off, or until the configured unlock_at
     * date arrives — whichever happens first. Without unlock_at the only way
     * out is the manual toggle.
     */
    public function isLocked(): bool
    {
        if (! $this->is_locked) {
            return false;
        }

        return ! ($this->unlock_at && now()->startOfDay()->gte($this->unlock_at));
    }

    /**
     * True when this session belongs to no stage, or to an active one. A session
     * on a deactivated stage is hidden from mentor and mentee alike (see
     * {@see isVisibleTo()}) and dropped from reports. Relies on the `stage`
     * relation, so eager-load it wherever this runs over a collection.
     */
    public function stageIsActive(): bool
    {
        return $this->stage_id === null || (bool) $this->stage?->isActive();
    }

    /**
     * Switched off by an admin, directly or through its stage. Deactivating is
     * the "does not exist for the audience" switch; locking (below) is the
     * "not yet" one. 'finished' is not 'inactive' — a closed session stays
     * visible as part of the record.
     */
    public function isDeactivated(): bool
    {
        return $this->status === 'inactive' || ! $this->stageIsActive();
    }

    /**
     * Does this role see the session at all? Hidden means "does not exist".
     * A deactivated session — or one on a deactivated stage — is hidden from
     * mentor and mentee alike. Admins, coordinators and clients never reach
     * this method for that gate: reports and the schedule exclude deactivated
     * sessions through {@see scopeAvailableToAudience()}.
     */
    public function isVisibleTo(string $role): bool
    {
        if ($this->isDeactivated()) {
            return $role !== User::ROLE_PARTICIPANT && $role !== User::ROLE_FACILITATOR;
        }

        return match ($role) {
            User::ROLE_PARTICIPANT => (bool) $this->visible_to_participant,
            User::ROLE_FACILITATOR => (bool) $this->visible_to_facilitator,
            default => true,
        };
    }

    /** Listed for this role, but not enterable yet — rendered as "Próximamente". */
    public function isComingSoonFor(string $role): bool
    {
        return $this->isVisibleTo($role) && $this->isLocked();
    }

    /**
     * The single gate for opening a session: the user's role must see it and it
     * must not be locked. Every entry point (session detail, session registration,
     * shared files) checks this rather than re-deriving the two conditions.
     */
    public function isEnterableBy(User $user): bool
    {
        return $this->isVisibleTo($user->role) && ! $this->isLocked();
    }
}
