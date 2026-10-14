<?php

namespace App\Http\Controllers;

use App\Models\RecurringLesson;
use App\Services\RecurringLessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestReservationController extends Controller
{
    private function reservation(Request $request): RecurringLesson
    {
        $input = $request->validate(['token' => ['required', 'string', 'size:64']]);

        return RecurringLesson::query()->where('management_token_hash', hash('sha256', $input['token']))
            ->whereNull('deleted_at')->firstOrFail();
    }

    public function show(Request $request, RecurringLessonService $reservations): JsonResponse
    {
        return response()->json($reservations->details($this->reservation($request)))->header('Cache-Control', 'no-store');
    }

    public function cancel(Request $request, RecurringLessonService $reservations): JsonResponse
    {
        $reservation = $this->reservation($request);
        $reservations->cancel($reservation);

        return response()->json($reservations->details($reservation->fresh()))->header('Cache-Control', 'no-store');
    }

    public function skip(Request $request, RecurringLessonService $reservations): JsonResponse
    {
        $reservation = $this->reservation($request);
        $input = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $reservations->skip($reservation, $input['date']);

        return response()->json($reservations->details($reservation->fresh()))->header('Cache-Control', 'no-store');
    }
}
