<?php

namespace App\Models;

class MakeupCredit extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'expiration_date' => 'date:Y-m-d', 'redeemed_at' => 'datetime'];
    }
}
