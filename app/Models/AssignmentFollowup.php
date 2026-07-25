<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single follow-up action the team logged with a dupla's mentor or mentee
 * (called, wrote, no answer, support, follow-up…). The history is kept so
 * coordinators, admins and the read-only client can see what was done.
 */
class AssignmentFollowup extends Model
{
    use BelongsToOrganization, HasFactory;

    /** @var array<string,string> contact_type value => label */
    public const CONTACT_TYPES = [
        'call' => 'Se llamó',
        'message' => 'Se escribió',
        'no_response' => 'No respondió',
        'support' => 'Se brindó soporte',
        'followup' => 'Se hizo seguimiento',
        'meeting' => 'Reunión',
        'other' => 'Otro',
    ];

    /** @var array<string,string> status value => label */
    public const STATUSES = [
        'done' => 'Realizado',
        'pending' => 'Pendiente',
        'in_progress' => 'En seguimiento',
        'no_response' => 'Sin respuesta',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'date',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** The mentor or mentee that was contacted (null = general / both). */
    public function contactedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_user_id');
    }

    /** The team member who registered this action. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contactTypeLabel(): string
    {
        return self::CONTACT_TYPES[$this->contact_type] ?? $this->contact_type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
