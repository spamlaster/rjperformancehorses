<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The single horse rule shared by slot listing and booking creation, so the
 * two can never disagree (port of Postgres available_horse_ids). Times are
 * naive "Y-m-d H:i:s" wall-time strings, which compare correctly as strings.
 *
 * A horse is free for [start, end) when it is active and not deleted, has
 * an active weekly window on that weekday
 * covering the whole lesson, has no horse block overlapping it, and is not in
 * booking_horses for an overlapping pending/confirmed booking. Never exposed
 * to clients (horse IDs are internal).
 */
final class HorseAvailability
{
    /**
     * @param  list<string>  $horseIds  active horses, sorted
     * @param  array<string, list<array{0:string,1:string}>>  $windows  per horse
     * @param  array<string, list<array{0:string,1:string}>>  $blocks  per horse
     * @param  array<string, list<array{0:string,1:string}>>  $bookings  per horse
     */
    private function __construct(
        private string $date,
        private array $horseIds,
        private array $windows,
        private array $blocks,
        private array $bookings,
    ) {}

    /**
     * Load everything needed to answer freeHorseIds() for lessons starting on
     * $date and overlapping [$rangeStart, $rangeEnd).
     */
    public static function load(string $lessonTypeId, string $date, string $rangeStart, string $rangeEnd, bool $weekly = false): self
    {
        $dayOfWeek = (int) date('w', strtotime($date));

        $horseIds = DB::table('horses')
            ->where('active', true)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();
        sort($horseIds, SORT_STRING);

        $at = fn (string $time) => "{$date} {$time}";

        $windows = [];
        foreach (DB::table('horse_recurring_availability')->whereIn('horse_id', $horseIds)
            ->where('active', true)->where('day_of_week', $dayOfWeek)->get() as $w) {
            $windows[$w->horse_id][] = [$at($w->start_time), $at($w->end_time)];
        }

        $blocks = [];
        foreach (DB::table('blocked_times')->whereIn('horse_id', $horseIds)
            ->where('date', $weekly ? '>=' : '=', $date)->get() as $b) {
            if ($weekly && CarbonImmutable::parse($b->date, 'UTC')->dayOfWeek !== $dayOfWeek) {
                continue;
            }
            $blocks[$b->horse_id][] = [$at($b->start_time), $at($b->end_time)];
        }

        $bookings = [];
        foreach (DB::table('booking_horses')
            ->join('bookings', 'bookings.id', '=', 'booking_horses.booking_id')
            ->whereIn('booking_horses.horse_id', $horseIds)
            ->whereIn('bookings.status', Booking::ACTIVE_STATUSES)
            ->whereNull('bookings.deleted_at')
            ->where('bookings.start_time', '<', $rangeEnd)
            ->where('bookings.end_time', '>', $rangeStart)
            ->get(['booking_horses.horse_id', 'bookings.start_time', 'bookings.end_time']) as $b) {
            $bookings[$b->horse_id][] = [$b->start_time, $b->end_time];
        }

        $reservations = RecurringLessonService::activeOnWeekday($dayOfWeek)
            ->when(! $weekly, fn ($q) => $q
                ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $date))
                ->whereNotExists(fn ($q) => $q->from('recurring_lesson_skips')
                    ->whereColumn('recurring_lesson_id', 'recurring_lessons.id')->where('date', $date)))
            ->with('horses')->get();
        foreach ($reservations as $reservation) {
            $from = "{$date} {$reservation->start_time}";
            $to = CarbonImmutable::parse($from, 'UTC')->addMinutes($reservation->duration_minutes)->format('Y-m-d H:i:s');
            $reservedIds = $reservation->horses->pluck('id')->all();
            if (! $reservedIds && $reservation->horse_id) {
                $reservedIds = [$reservation->horse_id];
            }
            foreach ($reservedIds as $horseId) {
                $bookings[$horseId][] = [$from, $to];
            }
        }

        return new self($date, $horseIds, $windows, $blocks, $bookings);
    }

    /** Free active horses for [$start, $end), in ID order. */
    public static function for(string $lessonTypeId, string $start, string $end): array
    {
        return self::load($lessonTypeId, substr($start, 0, 10), $start, $end)->freeHorseIds($start, $end);
    }

    /** Horses available for the standing weekly reservation, including future dated horse blocks. */
    public static function forWeekly(string $lessonTypeId, string $start, string $end): array
    {
        return self::load($lessonTypeId, substr($start, 0, 10), $start, $end, true)->freeHorseIds($start, $end);
    }

    /** @return list<string> */
    public function freeHorseIds(string $start, string $end): array
    {
        return array_values(array_filter($this->horseIds, function (string $id) use ($start, $end) {
            $covered = collect($this->windows[$id] ?? [])->contains(fn ($w) => $w[0] <= $start && $w[1] >= $end);

            return $covered
                && ! self::overlapsAny($this->blocks[$id] ?? [], $start, $end)
                && ! self::overlapsAny($this->bookings[$id] ?? [], $start, $end);
        }));
    }

    /** @param  list<array{0:string,1:string}>  $intervals */
    public static function overlapsAny(array $intervals, string $start, string $end): bool
    {
        foreach ($intervals as [$from, $to]) {
            if ($from < $end && $to > $start) {
                return true;
            }
        }

        return false;
    }
}
