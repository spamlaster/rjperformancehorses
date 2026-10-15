<?php

namespace Tests\Concerns;

use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\RiderAccountService;
use App\Services\WaiverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/**
 * Fixture from tests/availability.sql: one 60-minute lesson, three eligible
 * horses (only test-h1 active), instructor and horse windows 09:00–12:00 on
 * 2099-01-05.
 */
trait BuildsSchedule
{
    protected string $date = '2099-01-05';

    protected function buildSchedule(): void
    {
        $dow = (int) date('w', strtotime($this->date));
        $now = now();
        DB::table('waivers')->insert(['id' => 'test-waiver', 'title' => 'Test Riding Waiver', 'version' => 1, 'content' => 'Existing riding waiver test text.', 'effective_date' => '2000-01-01', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('lesson_types')->insert(['id' => 'test-lesson', 'name' => 'Test private', 'duration_minutes' => 60, 'price' => 1, 'required_horse_count' => 1, 'required_rider_count' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('customers')->insert(['id' => 'test-customer', 'first_name' => 'Test', 'last_name' => 'Customer', 'email' => 'test@example.com', 'created_at' => $now, 'updated_at' => $now]);
        foreach (['test-h1' => 'One', 'test-h2' => 'Two', 'test-h3' => 'Three'] as $id => $name) {
            DB::table('horses')->insert(['id' => $id, 'name' => "Availability Test {$name}", 'active' => $id === 'test-h1', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('horse_lesson_types')->insert(['id' => "test-e-{$id}", 'horse_id' => $id, 'lesson_type_id' => 'test-lesson', 'eligible' => true, 'created_at' => $now]);
            DB::table('horse_recurring_availability')->insert(['id' => "test-a-{$id}", 'horse_id' => $id, 'day_of_week' => $dow, 'start_time' => '09:00', 'end_time' => '12:00', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('instructor_availability')->insert(['id' => 'test-i', 'day_of_week' => $dow, 'start_time' => '09:00', 'end_time' => '12:00', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
    }

    protected function postRiderBooking(array $payload): TestResponse
    {
        if (! auth()->user()) {
            $this->actingAs(app(RiderAccountService::class)->signIn($payload['customer']['email'] ?? 'booking@example.com'));
        }

        return $this->postJson('/api/bookings/guest', $payload);
    }

    /** @return list<string> start times offered */
    protected function starts(?string $date = null, string $lesson = 'test-lesson'): array
    {
        return array_column(app(AvailabilityService::class)->bookableSlots($lesson, $date ?? $this->date), 'start_time');
    }

    protected function setActive(string $table, array $ids, bool $active): void
    {
        DB::table($table)->whereIn('id', $ids)->update(['active' => $active]);
    }

    protected function guestBooking(string $start, string $email = 'guest@example.com', array $riders = [['first_name' => 'Test', 'last_name' => 'Rider', 'date_of_birth' => '2010-01-01']], string $lesson = 'test-lesson'): array
    {
        $minutes = DB::table('lesson_types')->where('id', $lesson)->value('duration_minutes');

        return app(BookingService::class)->createGuestBooking([
            'lesson_type_id' => $lesson,
            'start_time' => $start,
            'end_time' => date('Y-m-d H:i:s', strtotime($start) + $minutes * 60),
            'customer' => ['first_name' => 'Test', 'last_name' => 'Guest', 'email' => $email, 'phone' => null],
            'riders' => $riders,
            'waiver' => $this->waiverPayload(count($riders)),
        ]);
    }

    protected function waiverPayload(int $riders = 1): array
    {
        $waiver = WaiverService::current();

        return [
            'id' => $waiver->id,
            'document_hash' => WaiverService::fingerprint($waiver),
            'signatures' => array_fill(0, $riders, [
                'signer_name' => 'Test Parent',
                'relationship_to_rider' => 'parent',
                'accepted' => true,
                'authorized' => true,
            ]),
        ];
    }

    protected function insertBooking(string $id, string $start, string $end, string $status = 'confirmed', array $horses = ['test-h1']): void
    {
        $now = now();
        DB::table('bookings')->insert(['id' => $id, 'customer_id' => 'test-customer', 'lesson_type_id' => 'test-lesson', 'horse_id' => $horses[0] ?? 'test-h1', 'start_time' => $start, 'end_time' => $end, 'status' => $status, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($horses as $i => $horse) {
            DB::table('booking_horses')->insert(['id' => "{$id}-bh{$i}", 'booking_id' => $id, 'horse_id' => $horse, 'created_at' => $now]);
        }
    }
}
