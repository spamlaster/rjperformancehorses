<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rider extends Model
{
    protected function casts(): array
    {
        return ['date_of_birth' => 'date:Y-m-d', 'deleted_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
