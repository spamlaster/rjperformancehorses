<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horse extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean', 'age' => 'integer', 'deleted_at' => 'datetime'];
    }

    public function lessonTypes(): BelongsToMany
    {
        return $this->belongsToMany(LessonType::class, 'horse_lesson_types')->withPivot('eligible');
    }

    public function recurringAvailability(): HasMany
    {
        return $this->hasMany(HorseRecurringAvailability::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(HorseOwner::class, 'horse_owner_relationships', 'horse_id', 'owner_id')
            ->withPivot('ownership_type', 'notes');
    }
}
