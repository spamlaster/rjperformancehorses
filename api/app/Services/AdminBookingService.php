<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingHorse;
use App\Models\BookingRider;
use App\Models\BusinessConfig;
use App\Models\Customer;
use App\Models\LessonType;
use App\Models\RecurringLesson;
use App\Models\Rider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminBookingService
{
    /** Record an existing commitment, including times outside public availability windows. */
    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->first();
            $lesson = LessonType::query()->where('active', true)->findOrFail($data['lesson_type_id']);
            $riders = Rider::query()->whereIn('id', $data['rider_ids'])->whereNull('deleted_at')->whereNull('merged_into_id')->get();
            $primary = $riders->firstWhere('id', $data['rider_ids'][0]);
            $customerId = $data['customer_id'] ?? $primary?->customer_id;
            if (! $customerId && $primary?->email) {
                $email = mb_strtolower(trim($primary->email));
                $customerId = (Customer::query()->where('email', $email)->whereNull('deleted_at')->first()
                    ?? Customer::create(['first_name' => $primary->first_name, 'last_name' => $primary->last_name,
                        'email' => $email, 'phone' => $primary->phone]))->id;
            }
            if (! $customerId) {
                throw ValidationException::withMessages(['customer_id' => 'Select a booking contact, or add an email to the rider first.']);
            }
            $data['customer_id'] = $customerId;
            if ($riders->count() !== $lesson->required_rider_count
                || $riders->contains(fn ($rider) => $rider->customer_id && $rider->customer_id !== $data['customer_id'])) {
                throw ValidationException::withMessages(['rider_ids' => 'Choose the required number of riders belonging to the selected contact.']);
            }
            $horseIds = $data['horse_ids'];
            $activeCount = DB::table('horses')->whereIn('id', $horseIds)->where('active', true)->whereNull('deleted_at')->count();
            if (count($horseIds) !== $lesson->required_horse_count || $activeCount !== count($horseIds)) {
                throw ValidationException::withMessages(['horse_ids' => "Choose {$lesson->required_horse_count} different active horse(s) for this lesson."]);
            }

            $startAt = CarbonImmutable::parse("{$data['date']} {$data['time']}", 'UTC');
            $endAt = $startAt->addMinutes($lesson->duration_minutes);
            $start = $startAt->format('Y-m-d H:i:s');
            $end = $endAt->format('Y-m-d H:i:s');
            $startTimestamp = BusinessTime::toTimestamp($start, BusinessConfig::timezone());
            $endTimestamp = BusinessTime::toTimestamp($end, BusinessConfig::timezone());
            if ($lesson->duration_minutes <= 0 || $startTimestamp === null || $endTimestamp === null
                || $startTimestamp <= now()->timestamp || $endTimestamp - $startTimestamp !== $lesson->duration_minutes * 60
                || $startAt->toDateString() !== $endAt->toDateString()) {
                throw ValidationException::withMessages(['time' => 'Choose a valid future session that finishes on the same day.']);
            }

            $weekly = $data['weekly'] ?? false;
            $conflicts = $weekly ? RecurringLessonService::weeklyInstructorConflicts($data['date'])
                : RecurringLessonService::instructorConflicts($data['date']);
            $dated = Booking::query()->whereIn('status', Booking::ACTIVE_STATUSES)->whereNull('deleted_at')
                ->where('start_time', '<', $end)->where('end_time', '>', $start)->exists();
            $blocks = DB::table('blocked_times')->where('date', $weekly ? '>=' : '=', $data['date'])
                ->where(fn ($q) => $q->whereNull('horse_id')->orWhereIn('horse_id', $horseIds))->get();
            foreach ($blocks as $block) {
                if (! $weekly || CarbonImmutable::parse($block->date, 'UTC')->dayOfWeek === $startAt->dayOfWeek) {
                    $conflicts[] = ["{$data['date']} {$block->start_time}", "{$data['date']} {$block->end_time}"];
                }
            }
            if ($dated || HorseAvailability::overlapsAny($conflicts, $start, $end)) {
                throw ValidationException::withMessages(['time' => 'This time overlaps an existing booking, weekly reservation, or blocked time.']);
            }

            $reservation = null;
            if ($weekly) {
                $reservation = RecurringLesson::create([
                    'customer_id' => $data['customer_id'], 'rider_id' => $riders->first()->id,
                    'lesson_type_id' => $lesson->id, 'horse_id' => $horseIds[0],
                    'day_of_week' => $startAt->dayOfWeek, 'start_time' => $data['time'],
                    'duration_minutes' => $lesson->duration_minutes, 'starts_on' => $data['date'], 'status' => 'active',
                    'management_token_hash' => hash('sha256', random_bytes(32)),
                ]);
                $reservation->horses()->attach($horseIds);
                $reservation->riders()->attach($data['rider_ids']);
            }
            $booking = Booking::create([
                'customer_id' => $data['customer_id'], 'lesson_type_id' => $lesson->id, 'horse_id' => $horseIds[0],
                'start_time' => $start, 'end_time' => $end, 'status' => 'confirmed',
                'booking_type' => $weekly ? 'recurring' : 'regular', 'recurring_lesson_id' => $reservation?->id,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($horseIds as $horseId) {
                BookingHorse::create(['booking_id' => $booking->id, 'horse_id' => $horseId]);
            }
            foreach ($data['rider_ids'] as $riderId) {
                BookingRider::create(['booking_id' => $booking->id, 'rider_id' => $riderId]);
            }

            return $booking;
        });
    }
}
