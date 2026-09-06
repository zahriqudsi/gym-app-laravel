<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'discount_cents' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
