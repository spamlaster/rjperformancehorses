<?php

namespace App\Models;

class BlockedTime extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }
}
