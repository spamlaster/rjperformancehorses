<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestBookingRequest;
use App\Models\BusinessConfig;
use App\Models\LessonType;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoints anyone can call (the old anon RPCs and public reads). */
class PublicController extends Controller
{
    public function lessonTypes(): JsonResponse
    {
        return response()->json(LessonType::query()->where('active', true)->orderBy('id')->get());
    }

    public function config(): JsonResponse
    {
        return response()->json(['business_timezone' => BusinessConfig::timezone()]);
    }

    /** Times only; never exposes horse IDs. */
    public function slots(Request $request, AvailabilityService $availability): JsonResponse
    {
        $input = $request->validate([
            'lesson_type_id' => ['required', 'string', 'max:36'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($availability->bookableSlots($input['lesson_type_id'], $input['date']));
    }

    public function createGuestBooking(StoreGuestBookingRequest $request, BookingService $bookings): JsonResponse
    {
        $result = $bookings->createGuestBooking($request->validated());

        return response()->json([
            'success' => true,
            ...$result,
            'message' => 'Booking created successfully',
        ], 201)->header('Cache-Control', 'no-store');
    }
}
