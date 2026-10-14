<?php

namespace Tests\Feature;

use App\Models\Rider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

class AdminBookingTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchedule();
        Rider::create(['id' => 'saved-rider', 'customer_id' => 'test-customer', 'first_name' => 'Saved', 'last_name' => 'Rider']);
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'customer_id' => 'test-customer', 'lesson_type_id' => 'test-lesson',
            'rider_ids' => ['saved-rider'], 'horse_ids' => ['test-h1'],
            'date' => $this->date, 'time' => '09:00', 'weekly' => false,
        ], $changes);
    }

    public function test_only_admins_can_add_sessions(): void
    {
        $this->postJson('/api/admin/bookings', $this->payload())->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/admin/bookings', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_session_is_assigned_to_saved_rider_and_blocks_public_booking_until_cancelled(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $id = $this->postJson('/api/admin/bookings', $this->payload())->assertCreated()->assertJsonPath('status', 'confirmed')->json('id');
        $this->assertDatabaseHas('booking_riders', ['booking_id' => $id, 'rider_id' => 'saved-rider']);
        $this->assertDatabaseHas('booking_horses', ['booking_id' => $id, 'horse_id' => 'test-h1']);
        $this->assertDatabaseCount('riders', 1);
        $this->assertDatabaseCount('waiver_acceptances', 0);
        $this->getJson('/api/admin/bookings')->assertOk()->assertJsonPath('0.rider_names', ['Saved Rider']);
        $this->assertNotContains('09:00', $this->starts());
        $this->postJson('/api/admin/bookings', $this->payload(['time' => '09:30']))->assertUnprocessable()->assertJsonValidationErrors('time');
        $this->postJson("/api/admin/bookings/{$id}/cancel")->assertOk();
        $this->assertContains('09:00', $this->starts());
    }

    public function test_admin_can_record_a_session_outside_public_availability(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson('/api/admin/bookings', $this->payload(['time' => '15:15']))->assertCreated()
            ->assertJsonPath('start_time', "{$this->date}T15:15:00")->assertJsonPath('end_time', "{$this->date}T16:15:00");
    }

    public function test_standalone_rider_can_use_their_email_as_the_booking_contact(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Rider::query()->findOrFail('saved-rider')->update(['customer_id' => null, 'email' => 'standalone@example.com']);
        $id = $this->postJson('/api/admin/bookings', $this->payload(['customer_id' => null]))->assertCreated()->json('customer_id');
        $this->assertDatabaseHas('customers', ['id' => $id, 'email' => 'standalone@example.com']);
        $this->assertDatabaseHas('booking_riders', ['rider_id' => 'saved-rider']);
        $this->assertDatabaseCount('riders', 1);
    }

    public function test_weekly_session_blocks_future_weeks_and_cancellation_releases_them(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $id = $this->postJson('/api/admin/bookings', $this->payload(['weekly' => true]))->assertCreated()->json('id');
        $this->assertDatabaseHas('recurring_lesson_riders', ['rider_id' => 'saved-rider']);
        $this->assertNotContains('09:00', $this->starts('2099-01-12'));
        $this->postJson('/api/admin/bookings', $this->payload(['date' => '2099-01-12']))->assertUnprocessable();
        $this->postJson("/api/admin/bookings/{$id}/cancel")->assertOk();
        $this->assertContains('09:00', $this->starts('2099-01-12'));
    }

    public function test_weekly_session_cannot_overlap_a_future_dated_commitment(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->insertBooking('future', '2099-01-12 09:30', '2099-01-12 10:30');
        $this->postJson('/api/admin/bookings', $this->payload(['weekly' => true]))->assertUnprocessable()->assertJsonValidationErrors('time');
        $this->assertDatabaseCount('recurring_lessons', 0);
    }

    public function test_rejects_wrong_contact_rider_count_and_ineligible_horses(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        DB::table('customers')->insert(['id' => 'other-contact', 'first_name' => 'Other', 'last_name' => 'Contact', 'email' => 'other@example.com']);
        $this->postJson('/api/admin/bookings', $this->payload(['customer_id' => 'other-contact']))->assertUnprocessable()->assertJsonValidationErrors('rider_ids');
        $this->postJson('/api/admin/bookings', $this->payload(['rider_ids' => ['saved-rider', 'saved-rider']]))->assertUnprocessable();
        $this->postJson('/api/admin/bookings', $this->payload(['horse_ids' => ['test-h2']]))->assertUnprocessable()->assertJsonValidationErrors('horse_ids');
        DB::table('horse_lesson_types')->where('horse_id', 'test-h1')->update(['eligible' => false]);
        $this->postJson('/api/admin/bookings', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('horse_ids');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_rejects_blocked_and_past_times(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        DB::table('blocked_times')->insert(['id' => 'horse-block', 'horse_id' => 'test-h1', 'date' => $this->date, 'start_time' => '09:00', 'end_time' => '10:00']);
        $this->postJson('/api/admin/bookings', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('time');
        $this->postJson('/api/admin/bookings', $this->payload(['date' => '2000-01-01']))->assertUnprocessable()->assertJsonValidationErrors('time');
        $this->assertDatabaseCount('bookings', 0);
    }
}
