<?php

namespace Tests\Feature;

use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\HorseAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

class WeeklyReservationTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2098-12-01 12:00:00', 'America/Denver'));
        $this->buildSchedule();
    }

    private function week(int $weeks): string
    {
        return CarbonImmutable::parse($this->date)->addWeeks($weeks)->toDateString();
    }

    public function test_booking_holds_the_same_wall_time_indefinitely_and_preserves_boundaries(): void
    {
        $result = $this->guestBooking("{$this->date} 09:30:00");

        $this->assertDatabaseHas('bookings', ['id' => $result['booking_id'], 'booking_type' => 'recurring']);
        $this->assertDatabaseHas('recurring_lessons', ['id' => $result['recurring_lesson_id'], 'starts_on' => $this->date, 'status' => 'active']);
        $this->assertDatabaseHas('recurring_lesson_horses', ['recurring_lesson_id' => $result['recurring_lesson_id'], 'horse_id' => 'test-h1']);
        foreach ([1, 52, 520] as $week) {
            $this->assertSame(['10:30', '11:00'], $this->starts($this->week($week)));
            $this->assertSame([], HorseAvailability::for('test-lesson', "{$this->week($week)} 09:30:00", "{$this->week($week)} 10:30:00"));
        }
        $this->assertContains('10:30', $this->starts(), 'back-to-back lessons do not overlap');
        $this->expectException(SlotUnavailableException::class);
        $this->guestBooking("{$this->week(520)} 10:00:00", 'other@example.com');
    }

    public function test_completing_the_first_session_does_not_release_the_weekly_reservation(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        DB::table('bookings')->where('id', $result['booking_id'])->update(['status' => 'completed']);

        $this->assertNotContains('09:00', $this->starts($this->week(1)));
        $this->assertNotContains('09:00', $this->starts($this->week(520)));
        $this->assertDatabaseHas('recurring_lessons', ['id' => $result['recurring_lesson_id'], 'status' => 'active']);
    }

    public function test_customer_cancellation_releases_all_future_weeks_and_is_idempotent(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        $token = $result['management_token'];
        $this->postJson('/api/reservations/manage', ['token' => $token])->assertOk()->assertJsonPath('status', 'active')->assertJsonPath('start_time', '09:00');
        $this->assertNotSame($token, DB::table('recurring_lessons')->value('management_token_hash'));

        $this->postJson('/api/reservations/cancel', ['token' => $token])->assertOk()->assertJsonPath('status', 'cancelled');
        $this->postJson('/api/reservations/cancel', ['token' => $token])->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $result['booking_id'], 'status' => 'cancelled']);
        foreach ([0, 1, 520] as $week) {
            $this->assertContains('09:00', $this->starts($this->week($week)));
        }
        $replacement = $this->guestBooking("{$this->week(1)} 09:00:00", 'replacement@example.com');
        $this->assertDatabaseHas('recurring_lessons', ['id' => $replacement['recurring_lesson_id'], 'status' => 'active']);
    }

    public function test_skip_releases_only_the_dated_occurrence_and_keeps_the_standing_reservation(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        $body = ['token' => $result['management_token'], 'date' => $this->date];

        $this->postJson('/api/reservations/skip', $body)->assertOk()->assertJsonPath('status', 'active')->assertJsonPath('upcoming_sessions.0.skipped', true);
        $this->postJson('/api/reservations/skip', $body)->assertOk();

        $this->assertDatabaseCount('recurring_lesson_skips', 1);
        $this->assertDatabaseHas('bookings', ['id' => $result['booking_id'], 'status' => 'cancelled']);
        $datedSlots = app(AvailabilityService::class)->bookableSlots('test-lesson', $this->date, false);
        $this->assertContains('09:00', array_column($datedSlots, 'start_time'));
        $this->assertSame(['test-h1'], HorseAvailability::for('test-lesson', "{$this->date} 09:00:00", "{$this->date} 10:00:00"));
        $this->assertNotContains('09:00', $this->starts(), 'a skip cannot sell the following weeks to someone else');
        $this->assertNotContains('09:00', $this->starts($this->week(1)));
        $this->assertSame([], HorseAvailability::for('test-lesson', "{$this->week(1)} 09:00:00", "{$this->week(1)} 10:00:00"));
    }

    public function test_management_requires_the_private_token_and_does_not_expose_it_in_admin_reads(): void
    {
        $one = $this->guestBooking("{$this->date} 09:00:00");
        $two = $this->guestBooking("{$this->date} 10:00:00", 'second@example.com');

        $this->postJson('/api/reservations/manage', [])->assertUnprocessable();
        $this->postJson('/api/reservations/cancel', ['token' => str_repeat('0', 64), 'id' => $one['recurring_lesson_id']])->assertNotFound();
        $this->postJson('/api/reservations/skip', ['token' => str_repeat('0', 64), 'date' => $this->date])->assertNotFound();
        $this->assertDatabaseHas('recurring_lessons', ['id' => $one['recurring_lesson_id'], 'status' => 'active']);

        $this->postJson('/api/reservations/cancel', ['token' => $two['management_token'], 'id' => $one['recurring_lesson_id']])->assertOk();
        $this->assertDatabaseHas('recurring_lessons', ['id' => $one['recurring_lesson_id'], 'status' => 'active']);
        $this->actingAs(User::factory()->admin()->create());
        $response = $this->getJson('/api/admin/recurring-lessons')->assertOk();
        $this->assertStringNotContainsString('management_token', $response->getContent());
        $this->assertStringNotContainsString($one['management_token'], $response->getContent());
    }

    public function test_admin_can_cancel_and_guests_and_non_admins_cannot(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        $path = "/api/admin/recurring-lessons/{$result['recurring_lesson_id']}/cancel";

        $this->postJson($path)->assertUnauthorized();
        $this->postJson("/api/admin/bookings/{$result['booking_id']}/cancel")->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson($path)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson($path)->assertOk()->assertJsonPath('status', 'cancelled');

        $this->assertContains('09:00', $this->starts($this->week(520)));
    }

    public function test_admin_booking_action_cancels_the_entire_series_and_legacy_bookings(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        $this->insertBooking('legacy', "{$this->date} 11:00:00", "{$this->date} 12:00:00");
        $this->actingAs(User::factory()->admin()->create());

        $this->postJson("/api/admin/bookings/{$result['booking_id']}/cancel")->assertOk();
        $this->postJson('/api/admin/bookings/legacy/cancel')->assertOk();

        $this->assertDatabaseHas('recurring_lessons', ['id' => $result['recurring_lesson_id'], 'status' => 'cancelled']);
        $this->assertContains('09:00', $this->starts($this->week(520)));
        $this->assertDatabaseHas('bookings', ['id' => 'legacy', 'status' => 'cancelled']);
    }

    public function test_future_dated_commitments_prevent_a_conflicting_weekly_reservation(): void
    {
        $this->insertBooking('future', "{$this->week(52)} 09:00:00", "{$this->week(52)} 10:00:00");

        $this->assertNotContains('09:00', $this->starts());
        $this->assertContains('10:00', $this->starts());
        $this->expectException(SlotUnavailableException::class);
        $this->guestBooking("{$this->date} 09:00:00");
    }

    public function test_future_series_prevents_an_earlier_start_that_would_overlap_later(): void
    {
        $this->guestBooking("{$this->week(4)} 09:00:00");

        $this->assertNotContains('09:00', $this->starts());
        $this->assertContains('09:00', array_column(app(AvailabilityService::class)->bookableSlots('test-lesson', $this->date, false), 'start_time'));
    }

    public function test_two_rider_weekly_reservation_keeps_both_riders_and_horses(): void
    {
        DB::table('lesson_types')->update(['required_rider_count' => 2, 'required_horse_count' => 2]);
        $this->setActive('horses', ['test-h2'], true);
        $result = $this->guestBooking("{$this->date} 09:00:00", riders: [
            ['first_name' => 'First', 'last_name' => 'Sibling'],
            ['first_name' => 'Second', 'last_name' => 'Sibling'],
        ]);

        $this->assertSame(2, DB::table('recurring_lesson_riders')->where('recurring_lesson_id', $result['recurring_lesson_id'])->count());
        $this->assertSame(2, DB::table('recurring_lesson_horses')->where('recurring_lesson_id', $result['recurring_lesson_id'])->count());
        $this->postJson('/api/reservations/manage', ['token' => $result['management_token']])
            ->assertOk()->assertJsonCount(2, 'rider_names');
        $this->assertSame([], HorseAvailability::for('test-lesson', "{$this->week(1)} 09:00:00", "{$this->week(1)} 10:00:00"));
    }

    public function test_invalid_skip_dates_are_rejected_and_cancelled_series_cannot_be_skipped(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        $token = $result['management_token'];
        $this->postJson('/api/reservations/skip', ['token' => $token, 'date' => 'bad'])->assertUnprocessable();
        $this->postJson('/api/reservations/skip', ['token' => $token, 'date' => CarbonImmutable::parse($this->date)->addDay()->toDateString()])->assertUnprocessable();
        $this->postJson('/api/reservations/skip', ['token' => $token, 'date' => $this->week(-1)])->assertUnprocessable();

        $this->travelTo(CarbonImmutable::parse("{$this->date} 09:01:00", 'America/Denver'));
        $this->postJson('/api/reservations/skip', ['token' => $token, 'date' => $this->date])->assertUnprocessable();
        $this->postJson('/api/reservations/cancel', ['token' => $token])->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $result['booking_id'], 'status' => 'pending']);
        $this->postJson('/api/reservations/skip', ['token' => $token, 'date' => $this->week(1)])->assertUnprocessable();
    }

    public function test_admin_schedule_projects_future_sessions_and_omits_skips(): void
    {
        $result = $this->guestBooking("{$this->date} 09:00:00");
        $this->postJson('/api/reservations/skip', ['token' => $result['management_token'], 'date' => $this->week(1)])->assertOk();
        $this->travelTo(CarbonImmutable::parse("{$this->date} 12:00:00", 'America/Denver'));
        $this->actingAs(User::factory()->admin()->create());

        $rows = $this->getJson('/api/admin/bookings')->assertOk()->json();

        $starts = array_column($rows, 'start_time');
        $this->assertNotContains("{$this->week(1)}T09:00:00", $starts);
        $this->assertContains("{$this->week(2)}T09:00:00", $starts);
        $this->assertSame(1, Booking::query()->count(), 'projection does not create a finite booking horizon');
    }
}
