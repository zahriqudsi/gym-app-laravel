<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipFreeze extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
