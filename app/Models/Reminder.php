<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Reminder extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['subject', 'message'];

    protected function casts(): array
    {
        return [
            'subject' => 'array',
            'message' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    /** The date this reminder should fire for a given session. */
    public function dueDateFor(Session $session): ?CarbonInterface
    {
        $anchor = $this->anchor === 'session_end' ? $session->end_date : $session->start_date;
        if (! $anchor) {
            return null;
        }

        return match ($this->timing) {
            'before' => $anchor->copy()->subDays($this->days),
            'after' => $anchor->copy()->addDays($this->days),
            default => $anchor->copy(),
        };
    }
}
