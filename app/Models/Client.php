<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Client extends Model
{
    use BelongsToOrganization, HasFactory, HasTranslations, SoftDeletes;

    protected $guarded = ['id'];

    public array $translatable = ['welcome_text'];

    protected function casts(): array
    {
        return [
            'welcome_text' => 'array',
        ];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
