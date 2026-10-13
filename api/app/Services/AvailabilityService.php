<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BusinessConfig;
use App\Models\LessonType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Public bookable slots (port of Postgres get_bookable_slots).
 *
 * Candidates start every 30 minutes inside each active instructor window for
 * the weekday, and the whole lesson must fit in the window. A candidate is
 * offered when:
 * - it starts in the future (business timezone)
 * - it is a real wall time that does not cross a DST change
 * - no instructor block overlaps it
 * - no pending/confirmed booking overlaps it (one instructor)
 * - at least required_horse_count horses are free
 */
class AvailabilityService
{
    public const SLOT_INTERVAL_MINUTES = 30;

    /** @return list<array{start_time:string,end_time:string,available:bool}> */
    public function bookableSlots(string $lessonTypeId, string $date): array
    {
        $timezone = BusinessConfig::timezone();
        $lesson = LessonType::query()->whereKey($lessonTypeId)
            ->where('active', true)->where('duration_minutes', '>', 0)->first();

        if (! $lesson || $date < CarbonImmutable::now($timezone)->toDateString()) {
            return [];
        }

        $duration = $lesson->duration_minutes;
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, 'UTC');

        // Overlapping windows can produce the same start; keyed to emit it once.
        $candidates = [];
        foreach (DB::table('instructor_availability')->where('active', true)
            ->where('day_of_week', $day->dayOfWeek)->get() as $window) {
            $last = $day->setTimeFromTimeString($window->end_time)->subMinutes($duration);
            for ($start = $day->setTimeFromTimeString($window->start_time); $start <= $last; $start = $start->addMinutes(self::SLOT_INTERVAL_MINUTES)) {
                $candidates[$start->format('Y-m-d H:i:s')] = $start;
            }
        }
        if (! $candidates) {
            return [];
        }
        ksort($candidates);

        $rangeStart = array_key_first($candidates);
        $rangeEnd = end($candidates)->addMinutes($duration)->format('Y-m-d H:i:s');

        $instructorBlocks = DB::table('blocked_times')->whereNull('horse_id')->where('date', $date)->get()
            ->map(fn ($b) => ["{$date} {$b->start_time}", "{$date} {$b->end_time}"])->all();
        $bookings = DB::table('bookings')
            ->whereIn('status', Booking::ACTIVE_STATUSES)->whereNull('deleted_at')
            ->where('start_time', '<', $rangeEnd)->where('end_time', '>', $rangeStart)
            ->get(['start_time', 'end_time'])->map(fn ($b) => [$b->start_time, $b->end_time])->all();
        $horses = HorseAvailability::load($lessonTypeId, $date, $rangeStart, $rangeEnd);
        $now = time();

        $slots = [];
        foreach ($candidates as $start => $startAt) {
            $endAt = $startAt->addMinutes($duration);
            $end = $endAt->format('Y-m-d H:i:s');
            $startTs = BusinessTime::toTimestamp($start, $timezone);
            $endTs = BusinessTime::toTimestamp($end, $timezone);

            if ($startTs === null || $endTs === null || $startTs <= $now
                || $endTs - $startTs !== $duration * 60
                || HorseAvailability::overlapsAny($instructorBlocks, $start, $end)
                || HorseAvailability::overlapsAny($bookings, $start, $end)
                || count($horses->freeHorseIds($start, $end)) < $lesson->required_horse_count) {
                continue;
            }

            $slots[] = ['start_time' => $startAt->format('H:i'), 'end_time' => $endAt->format('H:i'), 'available' => true];
        }

        return $slots;
    }
}
