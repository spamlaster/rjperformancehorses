<?php

namespace App\Http\Controllers\Admin;

use App\Models\Horse;
use App\Models\HorseLessonType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorseController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Horse::query()->with('lessonTypes')
            ->unless($request->boolean('include_inactive', true), fn ($q) => $q->where('active', true))
            ->orderBy('id')->get()->map(fn (Horse $horse): array => $this->serialize($horse)));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, new Horse, partial: false);
    }

    public function update(Request $request, Horse $horse): JsonResponse
    {
        return $this->save($request, $horse, partial: true);
    }

    private function serialize(Horse $horse): array
    {
        return [...$horse->attributesToArray(), 'eligible_lesson_type_ids' => $horse->lessonTypes
            ->filter(fn ($lesson): bool => (bool) $lesson->pivot->eligible)->pluck('id')->values()->all()];
    }

    private function save(Request $request, Horse $horse, bool $partial): JsonResponse
    {
        $fields = $this->fields($request, $partial);
        $eligibility = $request->validate([
            'eligible_lesson_type_ids' => ['sometimes', 'array'],
            'eligible_lesson_type_ids.*' => ['required', 'string', 'distinct', 'exists:lesson_types,id'],
        ]);

        return DB::transaction(function () use ($horse, $fields, $eligibility, $partial): JsonResponse {
            $horse->fill($fields)->save();
            if (array_key_exists('eligible_lesson_type_ids', $eligibility)) {
                HorseLessonType::query()->where('horse_id', $horse->id)->update(['eligible' => false]);
                foreach ($eligibility['eligible_lesson_type_ids'] as $lessonId) {
                    HorseLessonType::query()->updateOrCreate(
                        ['horse_id' => $horse->id, 'lesson_type_id' => $lessonId], ['eligible' => true],
                    );
                }
            }

            return response()->json($this->serialize($horse->refresh()->load('lessonTypes')), $partial ? 200 : 201);
        });
    }

    /** Hard delete, as before; refused (409) while bookings reference the horse. */
    public function destroy(Horse $horse): JsonResponse
    {
        $horse->delete();

        return response()->json(['success' => true]);
    }

    private function fields(Request $request, bool $partial): array
    {
        return $this->validateFields($request, [
            'name' => ['required', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:50'],
            'age' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:50'],
            'height' => ['nullable', 'string', 'max:20'],
            'temperament' => ['nullable', 'string'],
            'experience_level' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ], $partial);
    }
}
