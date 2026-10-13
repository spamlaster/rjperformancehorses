<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class BookingController extends AdminController
{
    public function index(): JsonResponse
    {
        $bookings = Booking::query()->with('riders:id,first_name,last_name')->orderBy('start_time')->get();

        return response()->json($bookings->map(fn (Booking $b) => [
            ...$b->withoutRelations()->toArray(),
            'rider_names' => $b->riders->map(fn ($r) => "{$r->first_name} {$r->last_name}")->values(),
        ]));
    }
}
