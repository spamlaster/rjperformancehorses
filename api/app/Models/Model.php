<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Base for domain tables: string primary keys (new rows get ULIDs; existing
 * IDs such as "horse-1" stay valid) and naive ISO datetimes in JSON.
 * Controllers only ever pass validated input, so models are unguarded.
 */
abstract class Model extends Eloquent
{
    use HasUlids;

    protected $guarded = [];

    public function isValidUniqueId($value): bool
    {
        return is_string($value) && $value !== '' && strlen($value) <= 36;
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }
}
