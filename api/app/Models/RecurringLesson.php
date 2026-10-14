<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RecurringLesson extends Model
{
    protected $hidden = ['management_token_hash'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'duration_minutes' => 'integer', 'starts_on' => 'date', 'cancelled_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lessonType(): BelongsTo
    {
        return $this->belongsTo(LessonType::class);
    }

    public function horses(): BelongsToMany
    {
        return $this->belongsToMany(Horse::class, 'recurring_lesson_horses');
    }

    public function riders(): BelongsToMany
    {
        return $this->belongsToMany(Rider::class, 'recurring_lesson_riders');
    }
}
