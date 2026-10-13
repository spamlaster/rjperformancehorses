<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorseRecurringAvailability extends Model
{
    protected $table = 'horse_recurring_availability';

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'active' => 'boolean'];
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }
}
