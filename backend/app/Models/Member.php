<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'joined_on' => 'date',
            'current_expiry_on' => 'date',
            'tags' => 'array',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTrainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_trainer_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function activeMembership(): ?Membership
    {
        return $this->memberships()
            ->where('status', 'active')
            ->orderByDesc('ends_on')
            ->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
