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

    public function update(Request $request, LessonType $lessonType): JsonResponse
    {
        $lessonType->update($this->validateFields($request, [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'difficulty_level' => ['nullable', 'string', 'max:50'],
            'max_group_size' => ['nullable', 'integer', 'min:1'],
        ], partial: true));

        return response()->json($lessonType);
    }
}
