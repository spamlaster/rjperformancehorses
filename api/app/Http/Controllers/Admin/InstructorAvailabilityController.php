<?php

namespace App\Http\Controllers\Admin;

use App\Models\InstructorAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorAvailabilityController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(InstructorAvailability::query()->orderBy('day_of_week')->orderBy('start_time')->get());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(InstructorAvailability::create($this->fields($request, partial: false))->refresh(), 201);
    }

    public function update(Request $request, InstructorAvailability $instructorAvailability): JsonResponse
    {
        $instructorAvailability->update($this->fields($request, partial: true));

        return response()->json($instructorAvailability);
    }

    public function destroy(InstructorAvailability $instructorAvailability): JsonResponse
    {
        $instructorAvailability->delete();

        return response()->json(['success' => true]);
    }

    private function fields(Request $request, bool $partial): array
    {
        return $this->validateFields($request, [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', self::TIME],
            'end_time' => ['required', self::TIME],
            'active' => ['boolean'],
        ], $partial);
    }
}
