<?php

namespace App\Http\Controllers\Admin;

use App\Models\Horse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorseController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Horse::query()
            ->unless($request->boolean('include_inactive', true), fn ($q) => $q->where('active', true))
            ->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(Horse::create($this->fields($request, partial: false))->refresh(), 201);
    }

    public function update(Request $request, Horse $horse): JsonResponse
    {
        $horse->update($this->fields($request, partial: true));

        return response()->json($horse);
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
