<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\BookingHorse;
use App\Models\BookingRider;
use App\Models\BusinessConfig;
use App\Models\Customer;
use App\Models\LessonType;
use App\Models\Rider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Guest booking (port of Postgres create_guest_booking). Input is already
 * validated by StoreGuestBookingRequest (lesson active, exact rider count).
 */
class BookingService
{
    public function __construct(private AvailabilityService $availability) {}

    /**
     * @param  array{lesson_type_id:string,start_time:string,end_time:string,customer:array,riders:list<array>}  $data
     * @return array{booking_id:string,customer_id:string}
     *
     * @throws SlotUnavailableException
     */
    public function createGuestBooking(array $data): array
    {
        $start = CarbonImmutable::parse($data['start_time'], 'UTC')->format('Y-m-d H:i:s');
        $end = CarbonImmutable::parse($data['end_time'], 'UTC')->format('Y-m-d H:i:s');

        return DB::transaction(function () use ($data, $start, $end) {
            // One instructor: serialize booking creation so two requests cannot both pass
            // the recheck. Must be the first statement: InnoDB's snapshot for the reads
            // below is taken after this lock is held, so they see committed bookings.
            BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->first();

            $lesson = LessonType::query()->findOrFail($data['lesson_type_id']);
            $date = substr($start, 0, 10);

            // The requested time must be a slot offered right now (same rules, no drift).
            $offered = collect($this->availability->bookableSlots($lesson->id, $date))
                ->contains(fn ($slot) => "{$date} {$slot['start_time']}:00" === $start);
            $minutes = (strtotime($end) - strtotime($start)) / 60;
            if (! $offered || $minutes !== $lesson->duration_minutes) {
                throw new SlotUnavailableException;
            }

            $horseIds = HorseAvailability::for($lesson->id, $start, $end);
            if (count($horseIds) < $lesson->required_horse_count) {
                throw new SlotUnavailableException;
            }

            // Conservative deduplication by email (case-insensitive collation).
            $customer = Customer::query()->where('email', $data['customer']['email'])->whereNull('deleted_at')->first()
                ?? Customer::create([
                    'first_name' => $data['customer']['first_name'],
                    'last_name' => $data['customer']['last_name'],
                    'email' => $data['customer']['email'],
                    'phone' => $data['customer']['phone'] ?? null,
                ]);

            $riderIds = [];
            foreach ($data['riders'] as $input) {
                $riderIds[] = $this->matchOrCreateRider($customer, $input)->id;
            }

            // bookings.horse_id is for admin display only; booking_horses is authoritative.
            $booking = Booking::create([
                'customer_id' => $customer->id,
                'lesson_type_id' => $lesson->id,
                'horse_id' => $horseIds[0],
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'pending',
                'booking_type' => 'regular',
            ]);

            foreach (array_slice($horseIds, 0, $lesson->required_horse_count) as $horseId) {
                BookingHorse::create(['booking_id' => $booking->id, 'horse_id' => $horseId]);
            }
            foreach (array_unique($riderIds) as $riderId) {
                BookingRider::create(['booking_id' => $booking->id, 'rider_id' => $riderId]);
            }

            return ['booking_id' => $booking->id, 'customer_id' => $customer->id];
        });
    }

    /** Same customer + exact name, and same date of birth unless none was given. */
    private function matchOrCreateRider(Customer $customer, array $input): Rider
    {
        $dateOfBirth = $input['date_of_birth'] ?? null;

        return Rider::query()
            ->where('customer_id', $customer->id)
            ->where('first_name', $input['first_name'])
            ->where('last_name', $input['last_name'])
            ->when($dateOfBirth !== null, fn ($q) => $q->where('date_of_birth', $dateOfBirth))
            ->whereNull('deleted_at')
            ->first()
            ?? Rider::create([
                'customer_id' => $customer->id,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'date_of_birth' => $dateOfBirth,
                'riding_experience' => 'beginner',
            ]);
    }
}
