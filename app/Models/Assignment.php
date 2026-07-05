<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * extra sessions, ordered chronologically (by start date, then order).
     *
     * @return \Illuminate\Support\Collection<int,\App\Models\Session>
     */
    public function allSessions(): \Illuminate\Support\Collection
    {
        return $this->program->sessions
            ->concat($this->extraSessions)
            ->sortBy([
                fn ($s) => $s->start_date?->timestamp ?? PHP_INT_MAX,
                fn ($s) => $s->sort_order,
            ])
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

    /** Is this user part of the dupla? */
    public function involves(User $user): bool
    {
        return in_array($user->id, [$this->facilitator_id, $this->participant_id], true);
    }
}
