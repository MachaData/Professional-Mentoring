<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class EmailTemplate extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['subject', 'body'];

    protected function casts(): array
    {
        return [
            'subject' => 'array',
            'body' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Resolve the most specific active template for a key: a program-scoped one
     * wins over an org-wide one.
     */
    public static function resolve(int $organizationId, string $key, ?int $programId = null): ?self
    {
        return static::query()
            ->where('organization_id', $organizationId)
            ->where('key', $key)
            ->where('status', 'active')
            ->when($programId, fn ($q) => $q->where(fn ($q) => $q
                ->where('program_id', $programId)->orWhereNull('program_id')))
            ->orderByRaw('program_id IS NULL')  // non-null (program-scoped) first
            ->first();
    }
}
