<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

class RiderIdentityMigrationTest extends TestCase
{
    use BuildsSchedule, DatabaseMigrations;

    public function test_existing_exact_duplicates_are_grouped_without_deleting_riders_or_booking_history(): void
    {
        $migration = require database_path('migrations/2026_10_14_065359_add_rider_accounts_and_identity_keys.php');
        $migration->down();
        $this->buildSchedule();
        foreach ([
            ['legacy-1', 'Emma', 'Smith', '2010-01-01'],
            ['legacy-2', ' emma ', 'SMITH', '2010-01-01'],
            ['different-birthday', 'Emma', 'Smith', '2012-01-01'],
        ] as [$id, $first, $last, $birthday]) {
            DB::table('riders')->insert(['id' => $id, 'customer_id' => 'test-customer', 'first_name' => $first, 'last_name' => $last, 'date_of_birth' => $birthday]);
        }
        $this->insertBooking('history', "{$this->date} 09:00:00", "{$this->date} 10:00:00");
        DB::table('booking_riders')->insert(['id' => 'historical-link', 'booking_id' => 'history', 'rider_id' => 'legacy-2']);

        $migration->up();

        $this->assertDatabaseCount('riders', 3);
        $this->assertDatabaseHas('riders', ['id' => 'legacy-2', 'merged_into_id' => 'legacy-1']);
        $this->assertDatabaseHas('booking_riders', ['id' => 'historical-link', 'rider_id' => 'legacy-2']);
        $this->actingAs(User::factory()->admin()->create())->getJson('/api/admin/riders')->assertOk()->assertJsonCount(2);
    }
}
