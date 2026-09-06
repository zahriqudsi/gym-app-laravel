<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_float_cents' => 'integer',
            'expected_cash_cents' => 'integer',
            'counted_cash_cents' => 'integer',
            'variance_cents' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
