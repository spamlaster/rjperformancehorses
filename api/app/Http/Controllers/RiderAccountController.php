<?php

namespace App\Http\Controllers;

use App\Models\RecurringLesson;
use App\Services\RecurringLessonService;
use App\Services\RiderAccountService;
use App\Services\RiderIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderAccountController extends Controller
{
    public function show(Request $request, RiderAccountService $accounts, RecurringLessonService $reservations): JsonResponse
    {
        $customer = $accounts->customerFor($request->user());
        $weekly = RecurringLesson::query()->where('customer_id', $customer->id)->whereNull('deleted_at')
            ->with(['lessonType', 'riders', 'horses'])->orderBy('day_of_week')->get();

        return response()->json([
            'customer' => $customer,
            'riders' => $customer->riders()->whereNull('deleted_at')->whereNull('merged_into_id')->orderBy('first_name')->get(),
            'reservations' => $weekly->map(fn ($r) => $reservations->details($r)),
        ])->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, RiderAccountService $accounts): JsonResponse
    {
        $input = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        $customer = $accounts->customerFor($request->user());
        $customer->update($input);

        return response()->json($customer);
    }

    public function createRider(Request $request, RiderAccountService $accounts, RiderIdentityService $riders): JsonResponse
    {
        $input = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        return response()->json($riders->findOrCreate($accounts->customerFor($request->user()), $input), 201);
    }

    public function cancel(Request $request, string $id, RiderAccountService $accounts, RecurringLessonService $reservations): JsonResponse
    {
        $customer = $accounts->customerFor($request->user());
        $reservation = RecurringLesson::query()->whereKey($id)->where('customer_id', $customer->id)->whereNull('deleted_at')->firstOrFail();
        $reservations->cancel($reservation);

        return response()->json($reservations->details($reservation->fresh()));
    }

    public function skip(Request $request, string $id, RiderAccountService $accounts, RecurringLessonService $reservations): JsonResponse
    {
        $input = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $customer = $accounts->customerFor($request->user());
        $reservation = RecurringLesson::query()->whereKey($id)->where('customer_id', $customer->id)->whereNull('deleted_at')->firstOrFail();
        $reservations->skip($reservation, $input['date']);

        return response()->json($reservations->details($reservation->fresh()));
    }
}
