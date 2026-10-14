<?php

namespace App\Http\Controllers\Admin;

use App\Models\RecurringLesson;
use App\Services\RecurringLessonService;
use Illuminate\Http\JsonResponse;

class RecurringLessonController extends AdminController
{
    public function index(RecurringLessonService $reservations): JsonResponse
    {
        return response()->json(RecurringLesson::query()->whereNull('deleted_at')
            ->with(['lessonType', 'riders', 'horses'])->orderBy('day_of_week')->orderBy('start_time')
            ->get()->map(fn ($r) => $reservations->details($r)));
    }

    public function cancel(RecurringLesson $recurringLesson, RecurringLessonService $reservations): JsonResponse
    {
        abort_if($recurringLesson->deleted_at !== null, 404);
        $reservations->cancel($recurringLesson);

        return response()->json($reservations->details($recurringLesson->fresh()));
    }
}
