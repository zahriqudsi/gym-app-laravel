<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** The single active branch (helper for a one-location gym). */
    public static function current(): ?self
    {
        return static::query()->where('is_active', true)->orderBy('id')->first();
    }
}
