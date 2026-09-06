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

    /** The open session for a branch, if any. */
    public static function currentOpen(?int $branchId): ?self
    {
        return static::query()
            ->where('status', 'open')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('opened_at')
            ->first();
    }

    public function cashCollectedCents(): int
    {
        return (int) $this->payments()
            ->where('method', 'cash')
            ->where('status', 'completed')
            ->sum('amount_cents');
    }

    public function expectedCashCents(): int
    {
        return $this->opening_float_cents + $this->cashCollectedCents();
    }

    /** Collections during the session, keyed by method. */
    public function breakdownByMethod(): array
    {
        return $this->payments()
            ->where('status', 'completed')
            ->selectRaw('method, SUM(amount_cents) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }
}
