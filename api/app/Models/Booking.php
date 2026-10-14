<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** Statuses that occupy the instructor and horses. */
    const ACTIVE_STATUSES = ['pending', 'confirmed'];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'version' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function waiverAcceptances(): HasMany
    {
        return $this->hasMany(WaiverAcceptance::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lessonType(): BelongsTo
    {
        return $this->belongsTo(LessonType::class);
    }

    /** Authoritative horse reservations (booking_horses). */
    public function horses(): BelongsToMany
    {
        return $this->belongsToMany(Horse::class, 'booking_horses');
    }

    public function riders(): BelongsToMany
    {
        return $this->belongsToMany(Rider::class, 'booking_riders');
    }
}
