<?php

namespace App\Models;

class Waiver extends Model
{
    protected function casts(): array
    {
        return ['version' => 'integer', 'effective_date' => 'date:Y-m-d', 'active' => 'boolean'];
    }
}
