<?php

namespace App\Models;

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

    /** Roles that can create/edit/delete content in the panel. */
    public function canManageContent(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ORG_ADMIN], true);
    }

    // ---- Filament ------------------------------------------------------

    public function canAccessPanel(Panel $panel): bool
    {
        // Admin panel: superadmins, org-admins (full) and coordinators (read-only).
        // Facilitators/participants use the dedicated Blade/Livewire portals.
        return in_array($this->role, [
            self::ROLE_SUPERADMIN, self::ROLE_ORG_ADMIN, self::ROLE_COORDINATOR,
        ], true) && $this->status === 'active';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
