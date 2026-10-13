<?php

namespace App\Models;

class InstructorAvailability extends Model
{
    protected $table = 'instructor_availability';

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'active' => 'boolean'];
    }
}
