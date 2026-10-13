<?php

namespace App\Models;

class RecurringLesson extends Model
{
    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'duration_minutes' => 'integer', 'deleted_at' => 'datetime'];
    }
}
