<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'offset_days' => 'integer',
            'respect_quiet_hours' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
