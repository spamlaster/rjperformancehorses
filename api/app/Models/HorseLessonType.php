<?php

namespace App\Models;

class HorseLessonType extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['eligible' => 'boolean'];
    }
}
