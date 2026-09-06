<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'signup_fee_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function isSessionBased(): bool
    {
        return $this->billing_type === 'sessions';
    }
}
