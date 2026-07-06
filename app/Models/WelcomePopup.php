<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A welcome popup configured per program and per portal role (facilitator /
 * participant). Shown once in the portal when enabled and it has content.
 */
class WelcomePopup extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['title', 'body'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** Normalize a YouTube/Vimeo link to an embeddable URL, or null. */
    public function embedUrl(): ?string
    {
        $url = $this->video_url;
        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return $url;
    }
}
