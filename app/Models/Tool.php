<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
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

    public function url(): ?string
    {
        return $this->external_url ?: ($this->file_path ? \Storage::url($this->file_path) : null);
    }
}
