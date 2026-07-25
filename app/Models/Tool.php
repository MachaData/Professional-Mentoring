<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\ResourceOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Tool extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
        ];
    }

    public function relations(): HasMany
    {
        return $this->hasMany(ToolRelation::class);
    }

    /** Is this tool visible to the given role? */
    public function visibleTo(string $role): bool
    {
        return match ($this->visibility) {
            'all' => true,
            'both' => in_array($role, ['facilitator', 'participant'], true),
            default => $this->visibility === $role,
        };
    }

    /**
     * A tool pinned to a business unit is hidden from everyone outside it.
     * No business unit on the tool means "every business unit".
     */
    public function belongsToBusinessUnit(?string $businessUnit): bool
    {
        return blank($this->business_unit) || $this->business_unit === $businessUnit;
    }

    /**
     * Both gates a portal user must clear: role visibility and business unit.
     * Language is deliberately not a gate — a material in another language is
     * still listed, just labelled, so nobody loses access to a document because
     * their profile language differs from the file's.
     */
    public function visibleToUser(User $user): bool
    {
        return $this->visibleTo($user->role)
            && $this->belongsToBusinessUnit($user->business_unit);
    }

    /** "Español", "English"… or null when the material has no language set. */
    public function languageLabel(): ?string
    {
        return ResourceOptions::languageLabel($this->language);
    }

    /** Short badge text: ES, EN… empty when there is no language. */
    public function languageBadge(): ?string
    {
        return $this->language ? strtoupper($this->language) : null;
    }

    public function url(): ?string
    {
        return $this->external_url ?: ($this->file_path ? \Storage::url($this->file_path) : null);
    }
}
