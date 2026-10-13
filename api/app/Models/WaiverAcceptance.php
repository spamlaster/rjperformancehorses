<?php

namespace App\Models;

class WaiverAcceptance extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
