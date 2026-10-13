<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class BusinessConfig extends Eloquent
{
    const CREATED_AT = null;

    protected $table = 'business_config';

    protected $primaryKey = 'config_key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    public static function timezone(): string
    {
        return static::query()->whereKey('business_timezone')->value('config_value');
    }
}
