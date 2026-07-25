<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Assignment extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(SessionRecord::class);
    }

    /** Extra sessions added for this dupla only. */
    public function extraSessions(): HasMany
    {
        return $this->hasMany(Session::class)->orderBy('sort_order');
    }

    /**
     * The full session set for this dupla: the program curriculum plus its own
     * extra sessions, in program order (Sesión 1, 2, 3…). Ordering follows
     * sort_order — never the creation date or id — with number and id as
     * deterministic tie-breakers.
     *
     * @return Collection<int,Session>
     */
    public function allSessions(): Collection
    {
        return $this->program->sessions
            ->concat($this->extraSessions)
            ->sortBy(fn ($s) => [$s->sort_order, $s->number ?? PHP_INT_MAX, $s->id])
            ->values();
    }

    public function files(): HasMany
    {
        return $this->hasMany(SharedFile::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    /** Team follow-up history (contacts/actions) for this dupla. */
    public function followups(): HasMany
    {
        return $this->hasMany(AssignmentFollowup::class)->latest('contacted_at')->latest('id');
    }

    /** Is this user part of the dupla? */
    public function involves(User $user): bool
    {
        return in_array($user->id, [$this->facilitator_id, $this->participant_id], true);
    }
}
