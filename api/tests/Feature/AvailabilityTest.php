<?php

namespace Tests\Feature;

use App\Services\AvailabilityService;
use App\Services\HorseAvailability;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

/** Port of tests/availability.sql (case numbers match). */
class AvailabilityTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchedule();
    }

    public function test_instructor_windows(): void
    {
        $this->assertCount(5, $this->starts(), '01 instructor candidates');

        $this->setActive('instructor_availability', ['test-i'], false);
        $this->assertSame([], $this->starts(), '02 unavailable instructor');

        DB::table('instructor_availability')->where('id', 'test-i')->update(['active' => true, 'end_time' => '10:00']);
        DB::table('instructor_availability')->insert(['id' => 'test-i2', 'day_of_week' => date('w', strtotime($this->date)), 'start_time' => '11:00', 'end_time' => '12:00', 'active' => true]);
        $this->assertSame(['09:00', '11:00'], $this->starts(), '03 multiple windows; 04 gap and 05 window overrun excluded');

        DB::table('blocked_times')->insert(['id' => 'test-block', 'date' => $this->date, 'start_time' => '09:30', 'end_time' => '10:00']);
        $this->assertNotContains('09:00', $this->starts(), '06 instructor overlap removed');

        DB::table('blocked_times')->where('id', 'test-block')->update(['start_time' => '10:00', 'end_time' => '11:00']);
        $this->assertCount(2, $this->starts(), '07 back to back block boundary');
    }

    public function test_horse_rules(): void
    {
        $this->assertCount(5, $this->starts(), 'active horse with shared hours');

        DB::table('horse_lesson_types')->where('id', 'test-e-test-h1')->update(['eligible' => false]);
        $this->assertCount(5, $this->starts(), 'legacy lesson eligibility does not gate availability');
        DB::table('horse_lesson_types')->delete();
        $this->assertCount(5, $this->starts(), 'horse hours work without lesson eligibility setup');

        $this->setActive('horses', ['test-h1'], false);
        $this->assertSame([], $this->starts(), '10 inactive horse excluded');
        $this->setActive('horses', ['test-h1'], true);

        DB::table('horse_recurring_availability')->where('id', 'test-a-test-h1')->delete();
        $this->assertSame([], $this->starts(), '11 zero recurring rows unavailable');

        DB::table('horse_recurring_availability')->insert(['id' => 'test-a-test-h1', 'horse_id' => 'test-h1', 'day_of_week' => date('w', strtotime($this->date)), 'start_time' => '13:00', 'end_time' => '14:00', 'active' => true]);
        $this->assertSame([], $this->starts(), '12 horse outside window');

        DB::table('horse_recurring_availability')->where('id', 'test-a-test-h1')->update(['start_time' => '09:00', 'end_time' => '12:00']);
        DB::table('blocked_times')->insert(['id' => 'test-hblock', 'horse_id' => 'test-h1', 'date' => $this->date, 'start_time' => '09:00', 'end_time' => '10:00']);
        $this->assertNotContains('09:00', $this->starts(), '13 horse block removes horse');

        $this->setActive('horses', ['test-h2'], true);
        $this->assertContains('09:00', $this->starts(), '14 second horse preserves slot');

        $this->setActive('horses', ['test-h2'], false);
        DB::table('blocked_times')->where('id', 'test-hblock')->delete();
        DB::table('horse_recurring_availability')->where('id', 'test-a-test-h1')->update(['end_time' => '09:30']);
        $this->assertSame([], $this->starts(), '15 horse must cover full lesson');

        DB::table('horse_recurring_availability')->where('id', 'test-a-test-h1')->update(['end_time' => '12:00']);
        $this->assertCount(5, $this->starts(), '16 requires one with one');

        $this->setActive('horse_recurring_availability', ['test-a-test-h1'], false);
        $this->assertSame([], $this->starts(), '58 inactive horse window not counted');
    }

    public function test_required_horse_count(): void
    {
        $this->setActive('horses', ['test-h1'], false);
        $this->assertSame([], $this->starts(), '17 requires one with zero');

        $this->setActive('horses', ['test-h1', 'test-h2'], true);
        DB::table('lesson_types')->where('id', 'test-lesson')->update(['required_horse_count' => 2]);
        $this->assertCount(5, $this->starts(), '18 requires two with two');

        $this->setActive('horses', ['test-h2'], false);
        $this->assertSame([], $this->starts(), '19 requires two with one');

        $this->setActive('horses', ['test-h2', 'test-h3'], true);
        $this->assertCount(5, $this->starts(), '20 requires two with three');

        $this->setActive('horses', ['test-h3'], false);
        DB::table('blocked_times')->insert(['id' => 'test-hblock', 'horse_id' => 'test-h1', 'date' => $this->date, 'start_time' => '09:00', 'end_time' => '10:00']);
        $this->assertNotContains('09:00', $this->starts(), '21 two with one blocked');

        $this->setActive('horses', ['test-h3'], true);
        $this->assertContains('09:00', $this->starts(), '22 three with one blocked');
    }

    public function test_existing_bookings(): void
    {
        DB::table('lesson_types')->where('id', 'test-lesson')->update(['required_horse_count' => 2]);
        $this->setActive('horses', ['test-h2', 'test-h3'], true);
        $this->insertBooking('test-booking', "{$this->date} 09:00", "{$this->date} 10:00");
        $this->assertNotContains('09:00', $this->starts(), '23 multi horse booking also occupies instructor');

        foreach (['pending' => false, 'confirmed' => false, 'cancelled' => true, 'completed' => true] as $status => $offered) {
            DB::table('bookings')->where('id', 'test-booking')->update(['status' => $status]);
            $this->assertSame($offered, in_array('09:00', $this->starts()), "24–27 {$status}");
        }

        DB::table('bookings')->where('id', 'test-booking')->update(['status' => 'confirmed']);
        $this->assertContains('10:00', $this->starts(), '28 adjacent booking allowed');
        $this->assertNotContains('09:30', $this->starts(), '29 overlap rejected');

        DB::table('bookings')->where('id', 'test-booking')->update(['deleted_at' => now()]);
        $this->assertContains('09:00', $this->starts(), 'deleted booking does not block');
    }

    public function test_lesson_rules(): void
    {
        $this->setActive('lesson_types', ['test-lesson'], false);
        $this->assertSame([], $this->starts(), '30 inactive lesson');
        $this->assertSame([], $this->starts(null, 'not-a-uuid-or-id'), '31 invalid lesson safe');

        DB::table('lesson_types')->where('id', 'test-lesson')->update(['active' => true, 'duration_minutes' => 90]);
        $slots = app(AvailabilityService::class)->bookableSlots('test-lesson', $this->date);
        $this->assertSame('10:30', collect($slots)->firstWhere('start_time', '09:00')['end_time'], '32 duration respected');

        DB::table('lesson_types')->where('id', 'test-lesson')->update(['duration_minutes' => 60]);
        $this->assertCount(5, $this->starts(), '33 changing duration changes result');
        $this->assertSame([], $this->starts('2000-01-01'), '34 past date');
    }

    public function test_business_timezone_and_dst(): void
    {
        // 35: midnight today in Kiritimati (UTC+14) has always already passed.
        $today = now('Pacific/Kiritimati');
        DB::table('instructor_availability')->insert(['id' => 'test-time', 'day_of_week' => $today->dayOfWeek, 'start_time' => '00:00', 'end_time' => '01:00', 'active' => true]);
        DB::table('horse_recurring_availability')->insert(['id' => 'test-time-h', 'horse_id' => 'test-h1', 'day_of_week' => $today->dayOfWeek, 'start_time' => '00:00', 'end_time' => '01:00', 'active' => true]);
        DB::table('business_config')->update(['config_value' => 'Pacific/Kiritimati']);
        $this->assertNotContains('00:00', $this->starts($today->toDateString()), '35 business timezone rejects elapsed local midnight');
        DB::table('business_config')->update(['config_value' => 'America/Denver']);

        DB::table('instructor_availability')->update(['active' => false]);
        DB::table('instructor_availability')->insert(['id' => 'test-dst', 'day_of_week' => 0, 'start_time' => '01:00', 'end_time' => '04:00', 'active' => true]);
        DB::table('horse_recurring_availability')->insert(['id' => 'test-dst-h', 'horse_id' => 'test-h1', 'day_of_week' => 0, 'start_time' => '01:00', 'end_time' => '04:00', 'active' => true]);

        $spring = $this->starts('2099-03-08');
        $this->assertSame([], array_intersect(['01:30', '02:00', '02:30'], $spring), '37 DST spring gap excluded');
        $this->assertContains('03:00', $spring, '37 after the gap offered');

        $this->assertSame(1, count(array_keys($this->starts('2099-11-01'), '01:30')), '46 fall DST repeated hour emitted once');

        DB::table('instructor_availability')->insert(['id' => 'test-duplicate-window', 'day_of_week' => 0, 'start_time' => '01:30', 'end_time' => '04:00', 'active' => true]);
        $fall = $this->starts('2099-11-01');
        $this->assertSame(count($fall), count(array_unique($fall)), '56 overlapping instructor windows emit distinct slots');

        DB::table('lesson_types')->where('id', 'test-lesson')->update(['duration_minutes' => 1500]);
        $this->assertSame([], $this->starts('2099-11-01'), '57 duration beyond day cannot wrap time');
    }

    public function test_booking_horses_is_authoritative(): void
    {
        $this->setActive('horses', ['test-h2', 'test-h3'], true);
        $this->insertBooking('test-legacy', "{$this->date} 11:00", "{$this->date} 12:00", horses: []);
        $this->assertContains('test-h3', HorseAvailability::for('test-lesson', "{$this->date} 11:00:00", "{$this->date} 12:00:00"),
            '71 bookings.horse_id without booking_horses ignored for horses');
        $this->assertNotContains('11:00', $this->starts(), '71 instructor still blocked');
    }

    public function test_schema_constraints(): void
    {
        $this->expectException(QueryException::class);
        DB::table('lesson_types')->where('id', 'test-lesson')->update(['required_horse_count' => 0]); // 54
    }
}
