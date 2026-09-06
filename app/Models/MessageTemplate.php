<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function resolve(string $key, string $channel = 'sms', string $language = 'en'): ?self
    {
        return static::query()
            ->where('key', $key)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('language', $language)->orWhere('language', 'en'))
            ->orderByRaw("language = ? desc", [$language])
            ->first();
    }
}
