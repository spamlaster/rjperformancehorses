<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\BusinessConfig;
use App\Models\RecurringLesson;
use App\Services\RecurringLessonService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends AdminController
{
    /** Upcoming occurrences are projected from the indefinite rule, never a finite reservation horizon. */
    public function index(RecurringLessonService $reservations): JsonResponse
    {
        $bookings = Booking::query()->whereNull('deleted_at')->with('riders:id,first_name,last_name')->orderBy('start_time')->get();
        $rows = $bookings->map(fn (Booking $b) => [
            ...$b->withoutRelations()->toArray(),
            'rider_names' => $b->riders->map(fn ($r) => "{$r->first_name} {$r->last_name}")->values()->all(),
        ])->all();

        foreach (RecurringLesson::query()->where('status', 'active')->whereNull('deleted_at')->with(['riders', 'horses', 'lessonType'])->get() as $reservation) {
            $details = $reservations->details($reservation);
            foreach ($details['upcoming_sessions'] as $session) {
                if ($session['skipped'] || $bookings->contains(fn ($b) => $b->recurring_lesson_id === $reservation->id && $b->start_time->toDateString() === $session['date'])) {
                    continue;
                }
                $start = CarbonImmutable::parse("{$session['date']} {$reservation->start_time}", 'UTC');
                $rows[] = [
                    'id' => "{$reservation->id}:{$session['date']}",
                    'customer_id' => $reservation->customer_id,
                    'lesson_type_id' => $reservation->lesson_type_id,
                    'horse_id' => $details['horse_ids'][0] ?? $reservation->horse_id,
                    'start_time' => $start->format('Y-m-d\TH:i:s'),
                    'end_time' => $start->addMinutes($reservation->duration_minutes)->format('Y-m-d\TH:i:s'),
                    'status' => 'confirmed',
                    'booking_type' => 'recurring',
                    'recurring_lesson_id' => $reservation->id,
                    'rider_names' => $details['rider_names'],
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,
                ];
            }
        }
        usort($rows, fn ($a, $b) => strcmp($a['start_time'], $b['start_time']));

        return response()->json($rows);
    }

    public function cancel(Booking $booking, RecurringLessonService $reservations): JsonResponse
    {
        abort_if($booking->deleted_at !== null, 404);
        if ($booking->recurring_lesson_id) {
            $reservations->cancel(RecurringLesson::query()->findOrFail($booking->recurring_lesson_id));
        } else {
            DB::transaction(function () use ($booking) {
                BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->first();
                Booking::query()->whereKey($booking->id)->whereIn('status', Booking::ACTIVE_STATUSES)
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);
            });
        }

        return response()->json(['success' => true]);
    }
}
