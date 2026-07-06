<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
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
            'images' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** Absolute URL of the header/banner image, or null when none is set. */
    public function headerImageUrl(): ?string
    {
        return $this->header_image
            ? Storage::disk('public')->url($this->header_image)
            : null;
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
