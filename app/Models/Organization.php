<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Organization extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $guarded = ['id'];

    public array $translatable = ['welcome_text', 'footer_text'];

    protected function casts(): array
    {
        return [
            'welcome_text' => 'array',
            'footer_text' => 'array',
            'is_operator' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function programTypes(): HasMany
    {
        return $this->hasMany(ProgramType::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
