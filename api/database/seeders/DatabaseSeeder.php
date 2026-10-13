<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Local development: reference data plus fictional sample data.
     * Production runs only ReferenceDataSeeder.
     */
    public function run(): void
    {
        $this->call([ReferenceDataSeeder::class, DevelopmentSeeder::class]);
    }
}
