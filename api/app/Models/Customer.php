<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected function casts(): array
    {
        return ['deleted_at' => 'datetime'];
    }

    public function riders(): HasMany
    {
        return $this->hasMany(Rider::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
