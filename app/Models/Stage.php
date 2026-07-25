<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Stage extends Model
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

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class)->orderBy('sort_order');
    }

    /**
     * An inactive stage is switched off by the admin: its sessions disappear
     * from the mentor and mentee portals, from the coordinator's reports and
     * from the schedule. Only content-managing admins keep seeing them.
     */
    public function isActive(): bool
    {
        return $this->status !== 'inactive';
    }
}
