<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SharedFile extends Model
{
    use BelongsToOrganization, HasFactory;

    /** @var array<string,string> value => label */
    public const TYPES = [
        'pdf' => 'PDF',
        'word' => 'Word',
        'excel' => 'Excel',
        'powerpoint' => 'PowerPoint',
        'image' => 'Imagen',
        'link' => 'Enlace externo',
        'text' => 'Texto',
        'other' => 'Otro',
    ];

    protected $guarded = ['id'];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): ?string
    {
        return $this->external_url ?: ($this->file_path ? Storage::url($this->file_path) : null);
    }

    /** Map a file extension to one of the supported types. */
    public static function typeFromExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'pdf',
            'doc', 'docx' => 'word',
            'xls', 'xlsx', 'csv' => 'excel',
            'ppt', 'pptx' => 'powerpoint',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'image',
            default => 'other',
        };
    }
}
