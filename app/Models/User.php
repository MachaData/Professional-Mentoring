<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ORG_ADMIN = 'organization_admin';

    public const ROLE_COORDINATOR = 'coordinator';

    public const ROLE_FACILITATOR = 'facilitator';

    public const ROLE_PARTICIPANT = 'participant';

    /**
     * Read-only "client" role: the organization's client may enter the admin
     * panel to see the desktop, reports and indicators (progress, duplas,
     * sessions, states and follow-up history) but cannot create, edit, delete
     * or configure anything. It is neither content manager nor dupla supervisor,
     * so every existing write gate (canManageContent / canSuperviseDuplas)
     * already excludes it — this role only ever reads.
     */
    public const ROLE_CLIENT = 'client';

    protected $fillable = [
        'organization_id', 'name', 'email', 'role', 'phone', 'photo',
        'position', 'area', 'business_unit', 'company', 'bio',
        'timezone', 'locale', 'invitation_status', 'invited_at',
        'must_change_password', 'status', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'datetime',
            'onboarding_seen_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignmentsAsFacilitator(): HasMany
    {
        return $this->hasMany(Assignment::class, 'facilitator_id');
    }

    public function assignmentsAsParticipant(): HasMany
    {
        return $this->hasMany(Assignment::class, 'participant_id');
    }

    // ---- Role helpers -------------------------------------------------

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isOrganizationAdmin(): bool
    {
        return $this->role === self::ROLE_ORG_ADMIN;
    }

    public function isCoordinator(): bool
    {
        return $this->role === self::ROLE_COORDINATOR;
    }

    public function isFacilitator(): bool
    {
        return $this->role === self::ROLE_FACILITATOR;
    }

    public function isParticipant(): bool
    {
        return $this->role === self::ROLE_PARTICIPANT;
    }

    /** Read-only client: sees the panel's reports and indicators, edits nothing. */
    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    /** Roles that can create/edit/delete content in the panel. */
    public function canManageContent(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ORG_ADMIN], true);
    }

    /**
     * Roles that supervise duplas: admins plus coordinators. They may edit
     * mentors/mentees, fill in per-dupla session data and add extra sessions,
     * but not touch the program configuration.
     */
    public function canSuperviseDuplas(): bool
    {
        return $this->canManageContent() || $this->isCoordinator();
    }

    // ---- Password recovery ----------------------------------------------

    /**
     * Send the platform's own recovery email instead of Laravel's default
     * English one, rendered in the language the user picked.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(
            (new ResetPasswordNotification($token))->locale($this->locale ?: config('app.locale'))
        );
    }

    // ---- Filament ------------------------------------------------------

    public function canAccessPanel(Panel $panel): bool
    {
        // Admin panel: superadmins, org-admins (full), coordinators (supervise
        // duplas, read-only elsewhere) and clients (fully read-only observers).
        // Facilitators/participants use the dedicated Blade/Livewire portals.
        return in_array($this->role, [
            self::ROLE_SUPERADMIN, self::ROLE_ORG_ADMIN,
            self::ROLE_COORDINATOR, self::ROLE_CLIENT,
        ], true) && $this->status === 'active';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
