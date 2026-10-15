<?php

namespace App\Http\Controllers\Admin;

use App\Models\LessonType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonTypeController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(LessonType::query()->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(LessonType::create($this->fields($request, partial: false))->refresh(), 201);
    }

    public function update(Request $request, LessonType $lessonType): JsonResponse
    {
        $lessonType->update($this->fields($request, partial: true));

        return response()->json($lessonType);
    }

    private function fields(Request $request, bool $partial): array
    {
        return $this->validateFields($request, [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'difficulty_level' => ['nullable', 'string', 'max:50'],
            'max_group_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'required_rider_count' => ['required', 'integer', 'min:1', 'max:20'],
            'required_horse_count' => ['required', 'integer', 'min:1', 'max:20'],
            'active' => ['boolean'],
        ], $partial);
    }
}
