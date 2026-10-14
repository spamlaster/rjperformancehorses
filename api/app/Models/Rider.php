<?php

namespace App\Models;

use App\Services\RiderIdentityService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rider extends Model
{
    protected $hidden = ['identity_key', 'merged_into_id'];

    protected static function booted(): void
    {
        static::saving(function (Rider $rider) {
            $rider->identity_key = $rider->merged_into_id ? null
                : RiderIdentityService::key($rider->getAttributes());
        });
    }

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
