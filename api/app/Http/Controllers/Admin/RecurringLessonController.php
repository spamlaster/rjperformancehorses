<?php

namespace App\Http\Controllers\Admin;

use App\Models\RecurringLesson;
use Illuminate\Http\JsonResponse;

class RecurringLessonController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(RecurringLesson::query()->whereNull('deleted_at')->orderBy('day_of_week')->orderBy('start_time')->get());
    }
}
