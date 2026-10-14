<?php

namespace Tests\Feature;

use App\Exceptions\SlotUnavailableException;
use App\Models\RecurringLesson;
use App\Services\AvailabilityService;
use App\Services\HorseAvailability;
use App\Services\RecurringLessonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

/** Port of the booking half of tests/availability.sql and tests/guest-booking.sql. */
class GuestBookingTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchedule();
    }

    /** Every candidate start: offered by the slot list <=> booking succeeds (attempts rolled back). */
    private function assertSlotsAndBookingAgree(string $label): void
    {
        $offered = $this->starts();
        foreach (['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'] as $time) {
            DB::beginTransaction();
            try {
                $this->guestBooking("{$this->date} {$time}:00", 'agree@example.com');
                $booked = true;
            } catch (SlotUnavailableException) {
                $booked = false;
            } finally {
                DB::rollBack();
            }
            $this->assertSame(in_array($time, $offered), $booked, "{$label}: disagree at {$time}");
        }
    }

    public function test_bookings_reserve_horses_and_agree_with_slots(): void
    {
        $this->setActive('horse_recurring_availability', ['test-a-test-h1'], false);
        $this->assertSlotsAndBookingAgree('59 no horse');

        $this->setActive('horse_recurring_availability', ['test-a-test-h1'], true);
        $this->setActive('horses', ['test-h2', 'test-h3'], true);
        $one = $this->guestBooking("{$this->date} 09:00:00", 'one@example.com');
        $this->assertSame(['test-h1'], DB::table('booking_horses')->where('booking_id', $one['booking_id'])->pluck('horse_id')->all(),
            '60/61 one-horse booking reserves its horse via booking_horses');
        $this->assertSame(['test-h2', 'test-h3'], HorseAvailability::for('test-lesson', "{$this->date} 09:30:00", "{$this->date} 10:30:00"),
            '62 booked horse removed from availability');

        $this->assertNotContains('09:00', $this->starts(), '63 booked time no longer offered');
        try {
            $this->guestBooking("{$this->date} 09:00:00", 'dup@example.com');
            $this->fail('63 booked time should not be bookable');
        } catch (SlotUnavailableException $e) {
            $this->assertSame('The requested time is no longer available', $e->getMessage());
        }

        app(RecurringLessonService::class)->cancel(RecurringLesson::findOrFail($one['recurring_lesson_id']));
        foreach (['cancelled' => '64', 'completed' => '65'] as $status => $case) {
            DB::table('bookings')->where('id', $one['booking_id'])->update(['status' => $status]);
            $this->assertCount(3, HorseAvailability::for('test-lesson', "{$this->date} 09:00:00", "{$this->date} 10:00:00"), "{$case} {$status} booking releases horse");
        }

        DB::table('lesson_types')->where('id', 'test-lesson')->update(['required_horse_count' => 2]);
        $two = $this->guestBooking("{$this->date} 10:00:00", 'two@example.com');
        $this->assertSame(2, DB::table('booking_horses')->where('booking_id', $two['booking_id'])->distinct()->count('horse_id'),
            '66/67 two-horse booking reserves two distinct horses');
        $this->assertSame(['test-h3'], HorseAvailability::for('test-lesson', "{$this->date} 10:30:00", "{$this->date} 11:30:00"),
            '68 both booked horses excluded from overlapping availability');
        $this->assertSlotsAndBookingAgree('69 after two-horse booking');

        DB::table('blocked_times')->insert(['id' => 'test-hblock2', 'horse_id' => 'test-h3', 'date' => $this->date, 'start_time' => '11:00', 'end_time' => '12:00']);
        $this->assertSlotsAndBookingAgree('70 horse count decides');
    }

    public function test_two_rider_booking_through_the_api(): void
    {
        DB::table('lesson_types')->where('id', 'test-lesson')->update(['required_horse_count' => 2, 'required_rider_count' => 2]);
        $this->setActive('horses', ['test-h2'], true);

        $response = $this->postJson('/api/bookings/guest', [
            'lesson_type_id' => 'test-lesson',
            'start_time' => "{$this->date}T10:00:00",
            'end_time' => "{$this->date}T11:00:00",
            'customer' => ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com', 'phone' => '555-5678'],
            'riders' => [
                ['first_name' => 'Jane', 'last_name' => 'Smith', 'date_of_birth' => '2010-01-15'],
                ['first_name' => 'Jack', 'last_name' => 'Smith', 'date_of_birth' => null],
            ],
        ]);

        $response->assertCreated()->assertJson(['success' => true, 'message' => 'Booking created successfully']);
        $bookingId = $response->json('booking_id');
        $this->assertSame(2, DB::table('booking_riders')->where('booking_id', $bookingId)->count(), 'both riders linked');
        $this->assertSame(2, DB::table('booking_horses')->where('booking_id', $bookingId)->distinct()->count('horse_id'), 'two distinct horses');
        $this->assertSame('pending', DB::table('bookings')->where('id', $bookingId)->value('status'));
        $this->assertNull(DB::table('riders')->where('first_name', 'Jack')->value('date_of_birth'), 'date of birth is optional');
    }

    public function test_validation_messages_match_the_old_rpc(): void
    {
        $base = [
            'lesson_type_id' => 'test-lesson',
            'start_time' => "{$this->date}T09:00:00",
            'end_time' => "{$this->date}T10:00:00",
            'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com'],
            'riders' => [['first_name' => 'A', 'last_name' => 'B']],
        ];

        $this->postJson('/api/bookings/guest', [...$base, 'customer' => ['first_name' => 'A', 'last_name' => 'B']])
            ->assertUnprocessable()->assertJsonFragment(['customer.email' => ['Customer email is required']]);
        $this->postJson('/api/bookings/guest', [...$base, 'lesson_type_id' => 'nope'])
            ->assertUnprocessable()->assertJsonPath('errors.lesson_type_id.0', 'Lesson type not found or inactive');
        $this->postJson('/api/bookings/guest', [...$base, 'riders' => [['first_name' => 'A', 'last_name' => 'B'], ['first_name' => 'C', 'last_name' => 'D']]])
            ->assertUnprocessable()->assertJsonPath('errors.riders.0', 'This lesson requires exactly 1 rider(s), but 2 were provided');
        $this->postJson('/api/bookings/guest', [...$base, 'end_time' => "{$this->date}T10:30:00"])
            ->assertStatus(409)->assertJsonPath('message', 'The requested time is no longer available');
        $this->postJson('/api/bookings/guest', [...$base, 'start_time' => "{$this->date}T09:15:00", 'end_time' => "{$this->date}T10:15:00"])
            ->assertStatus(409);

        $this->assertSame(0, DB::table('customers')->where('email', 'a@example.com')->count(), 'failed bookings leave no partial records');
    }

    public function test_customer_and_rider_deduplication(): void
    {
        $this->setActive('horses', ['test-h2', 'test-h3'], true);
        $this->guestBooking("{$this->date} 09:00:00", 'Jane@Example.com', [['first_name' => 'Emma', 'last_name' => 'Doe', 'date_of_birth' => '2012-05-01']]);
        $this->guestBooking("{$this->date} 10:00:00", 'jane@example.com', [['first_name' => 'Emma', 'last_name' => 'Doe', 'date_of_birth' => null]]);
        $this->assertSame(1, DB::table('customers')->count() - 1, 'email dedupe is case-insensitive');
        $this->assertSame(1, DB::table('riders')->count(), 'rider matched by name when no date of birth given');

        $this->guestBooking("{$this->date} 11:00:00", 'jane@example.com', [['first_name' => 'emma', 'last_name' => 'Doe', 'date_of_birth' => '2012-05-01']]);
        $this->assertSame(2, DB::table('riders')->count(), 'rider names match exactly (case-sensitive)');
    }

    public function test_browsing_dates_does_not_use_up_the_booking_rate_limit(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->getJson("/api/availability/slots?lesson_type_id=test-lesson&date={$this->date}")->assertOk();
        }

        $this->postJson('/api/bookings/guest', [])->assertUnprocessable();
    }

    public function test_slots_endpoint_is_public_and_exposes_times_only(): void
    {
        $response = $this->getJson("/api/availability/slots?lesson_type_id=test-lesson&date={$this->date}")->assertOk();
        $this->assertCount(5, $response->json(), '38 anon executes slot lookup');
        $this->assertSame(['available', 'end_time', 'start_time'], collect(array_keys($response->json(0)))->sort()->values()->all(), '45 only safe output');
        $this->assertSame(app(AvailabilityService::class)->bookableSlots('test-lesson', $this->date), $response->json());
    }
}
