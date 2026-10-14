<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiverAcceptance extends Model
{
    const UPDATED_AT = null;

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
