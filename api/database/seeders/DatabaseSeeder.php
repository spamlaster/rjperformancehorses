<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Fresh installs include the standard lesson catalog. Demo records are opt-in. */
    public function run(): void
    {
        $this->call(DefaultLessonTypesSeeder::class);
    }
}
