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

    public function isFacilitator(): bool
    {
        return $this->role === self::ROLE_FACILITATOR;
    }

    public function isParticipant(): bool
    {
        return $this->role === self::ROLE_PARTICIPANT;
    }

    // ---- Filament ------------------------------------------------------

    public function canAccessPanel(Panel $panel): bool
    {
        // Only the admin panel is Filament. Superadmins and org-admins enter;
        // facilitators/participants use the dedicated Blade/Livewire portals.
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ORG_ADMIN], true)
            && $this->status === 'active';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
