<?php

namespace Tests\Feature;

use App\Exceptions\SlotUnavailableException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

/**
 * Two real connections race for the last free slot: exactly one wins. Needs
 * committed data, so it migrates instead of wrapping the test in a transaction.
 *
 * @requires extension pcntl
 */
class ConcurrentBookingTest extends TestCase
{
    use BuildsSchedule, DatabaseMigrations;

    public function test_only_one_of_two_simultaneous_bookings_succeeds(): void
    {
        $this->raceBookings([$this->date, $this->date]);
    }

    public function test_different_first_dates_cannot_claim_the_same_weekly_time(): void
    {
        $this->raceBookings([$this->date, date('Y-m-d', strtotime($this->date.' +7 days'))]);
    }

    private function raceBookings(array $dates): void
    {
        $this->buildSchedule();
        $results = tempnam(sys_get_temp_dir(), 'rjph');
        $startAt = microtime(true) + 1.0;

        DB::disconnect();
        $pids = [];
        foreach (['racer-a@example.com', 'racer-b@example.com'] as $index => $email) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::reconnect();
                time_sleep_until($startAt);
                try {
                    $this->guestBooking("{$dates[$index]} 09:00:00", $email);
                    $outcome = 'booked';
                } catch (SlotUnavailableException) {
                    $outcome = 'unavailable';
                } catch (\Throwable $e) {
                    $outcome = 'error: '.$e->getMessage();
                }
                file_put_contents($results, $outcome.PHP_EOL, FILE_APPEND | LOCK_EX);
                posix_kill(getmypid(), SIGKILL); // skip PHPUnit shutdown in the child
            }
            $pids[] = $pid;
        }
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }
        DB::reconnect();

        $outcomes = file($results, FILE_IGNORE_NEW_LINES);
        sort($outcomes);
        unlink($results);
        $this->assertSame(['booked', 'unavailable'], $outcomes);
        $this->assertSame(1, DB::table('bookings')->count());
        $this->assertSame(1, DB::table('recurring_lessons')->count());
        $this->assertSame(1, DB::table('booking_horses')->count());
    }
}
