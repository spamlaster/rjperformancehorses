<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LessonType extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'duration_minutes' => 'integer',
            'max_group_size' => 'integer',
            'required_horse_count' => 'integer',
            'required_rider_count' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function horses(): BelongsToMany
    {
        return $this->belongsToMany(Horse::class, 'horse_lesson_types')->withPivot('eligible');
    }
}
