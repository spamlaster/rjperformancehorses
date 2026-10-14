<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BusinessConfig;
use App\Models\RecurringLesson;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringLessonService
{
    public static function activeOnWeekday(int $weekday): Builder
    {
        return RecurringLesson::query()->where('status', 'active')->whereNull('deleted_at')->where('day_of_week', $weekday);
    }

    /** Weekly booking candidates must be free in all following weeks, including future dated commitments. */
    public static function weeklyInstructorConflicts(string $date): array
    {
        $day = CarbonImmutable::parse($date, 'UTC');
        $intervals = self::activeOnWeekday($day->dayOfWeek)->get()->map(fn ($r) => [
            "{$date} {$r->start_time}",
            $day->setTimeFromTimeString($r->start_time)->addMinutes($r->duration_minutes)->format('Y-m-d H:i:s'),
        ])->all();

        foreach (Booking::query()->whereIn('status', Booking::ACTIVE_STATUSES)->whereNull('deleted_at')
            ->where('end_time', '>', "{$date} 00:00:00")->get() as $booking) {
            // Check actual dated intervals, including a legacy booking spanning midnight.
            $from = $booking->start_time->toImmutable();
            for ($on = $from->startOfDay(); $on <= $booking->end_time->startOfDay(); $on = $on->addDay()) {
                if ($on->toDateString() < $date || $on->dayOfWeek !== $day->dayOfWeek) {
                    continue;
                }
                $start = $on->isSameDay($from) ? $from->format('H:i:s') : '00:00:00';
                $end = $on->isSameDay($booking->end_time) ? $booking->end_time->format('H:i:s') : '24:00:00';
                $intervals[] = ["{$date} {$start}", "{$date} {$end}"];
            }
        }

        foreach (DB::table('blocked_times')->whereNull('horse_id')->where('date', '>=', $date)->get() as $block) {
            if (CarbonImmutable::parse($block->date, 'UTC')->dayOfWeek === $day->dayOfWeek) {
                $intervals[] = ["{$date} {$block->start_time}", "{$date} {$block->end_time}"];
            }
        }

        return $intervals;
    }

    /** Exact-date instructor occupancy: a skip releases only this occurrence. */
    public static function instructorConflicts(string $date): array
    {
        $day = CarbonImmutable::parse($date, 'UTC');

        return self::activeOnWeekday($day->dayOfWeek)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $date))
            ->whereNotExists(fn ($q) => $q->from('recurring_lesson_skips')
                ->whereColumn('recurring_lesson_id', 'recurring_lessons.id')->where('date', $date))
            ->get()->map(fn ($r) => [
                "{$date} {$r->start_time}",
                $day->setTimeFromTimeString($r->start_time)->addMinutes($r->duration_minutes)->format('Y-m-d H:i:s'),
            ])->all();
    }

    public function cancel(RecurringLesson $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->first();
            $reservation = RecurringLesson::query()->findOrFail($reservation->id);
            if ($reservation->status === 'cancelled') {
                return;
            }
            $reservation->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            Booking::query()->where('recurring_lesson_id', $reservation->id)
                ->whereIn('status', Booking::ACTIVE_STATUSES)
                ->where('start_time', '>', CarbonImmutable::now(BusinessConfig::timezone())->format('Y-m-d H:i:s'))
                ->update(['status' => 'cancelled', 'updated_at' => now()]);
        });
    }

    public function skip(RecurringLesson $reservation, string $date): void
    {
        DB::transaction(function () use ($reservation, $date) {
            BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->first();
            $reservation = RecurringLesson::query()->findOrFail($reservation->id);
            $day = CarbonImmutable::parse($date, 'UTC');
            $start = "{$date} {$reservation->start_time}";
            $timestamp = BusinessTime::toTimestamp($start, BusinessConfig::timezone());
            if ($reservation->status !== 'active' || $reservation->deleted_at
                || ($reservation->starts_on && $date < $reservation->starts_on->toDateString())
                || $day->dayOfWeek !== $reservation->day_of_week
                || $timestamp === null || $timestamp <= CarbonImmutable::now()->timestamp) {
                throw ValidationException::withMessages(['date' => 'Choose an upcoming session in your active weekly reservation.']);
            }
            DB::table('recurring_lesson_skips')->insertOrIgnore([
                'recurring_lesson_id' => $reservation->id, 'date' => $date, 'created_at' => now(),
            ]);
            Booking::query()->where('recurring_lesson_id', $reservation->id)
                ->whereIn('status', Booking::ACTIVE_STATUSES)->whereDate('start_time', $date)
                ->update(['status' => 'cancelled', 'updated_at' => now()]);
        });
    }

    /** Customer-safe details, shared with the admin weekly list. */
    public function details(RecurringLesson $reservation): array
    {
        $reservation->loadMissing(['lessonType', 'riders', 'horses']);
        $skipped = DB::table('recurring_lesson_skips')->where('recurring_lesson_id', $reservation->id)->orderBy('date')->pluck('date')->all();
        $now = CarbonImmutable::now(BusinessConfig::timezone());
        $date = $now->startOfDay();
        if ($reservation->starts_on && $reservation->starts_on->toDateString() > $date->toDateString()) {
            $date = CarbonImmutable::parse($reservation->starts_on->toDateString(), BusinessConfig::timezone());
        }
        while ($date->dayOfWeek !== $reservation->day_of_week) {
            $date = $date->addDay();
        }
        if ($date->setTimeFromTimeString($reservation->start_time) <= $now) {
            $date = $date->addWeek();
        }
        $upcoming = [];
        if ($reservation->status === 'active' && ! $reservation->deleted_at) {
            for ($i = 0; $i < 8; $i++, $date = $date->addWeek()) {
                $upcoming[] = ['date' => $date->toDateString(), 'skipped' => in_array($date->toDateString(), $skipped, true)];
            }
        }

        return [
            'id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'lesson_type_id' => $reservation->lesson_type_id,
            'lesson_name' => $reservation->lessonType?->name,
            'day_of_week' => $reservation->day_of_week,
            'start_time' => substr($reservation->start_time, 0, 5),
            'duration_minutes' => $reservation->duration_minutes,
            'starts_on' => $reservation->starts_on?->toDateString(),
            'status' => $reservation->status,
            'business_timezone' => BusinessConfig::timezone(),
            'rider_names' => $reservation->riders->map(fn ($r) => "{$r->first_name} {$r->last_name}")->values()->all(),
            'horse_ids' => $reservation->horses->pluck('id')->all(),
            'skipped_dates' => $skipped,
            'upcoming_sessions' => $upcoming,
        ];
    }
}
