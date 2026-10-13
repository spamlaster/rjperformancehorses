<?php

namespace App\Models;

class CalendarConnection extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean', 'sync_enabled' => 'boolean', 'last_sync_at' => 'datetime'];
    }
}
