<?php

namespace App\Http\Controllers\Admin;

use App\Models\HorseRecurringAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorseAvailabilityController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(HorseRecurringAvailability::query()
            ->orderBy('horse_id')->orderBy('day_of_week')->orderBy('start_time')->get());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(HorseRecurringAvailability::create($this->fields($request, partial: false))->refresh(), 201);
    }

    public function update(Request $request, HorseRecurringAvailability $horseAvailability): JsonResponse
    {
        $horseAvailability->update($this->fields($request, partial: true));

        return response()->json($horseAvailability);
    }

    public function destroy(HorseRecurringAvailability $horseAvailability): JsonResponse
    {
        $horseAvailability->delete();

        return response()->json(['success' => true]);
    }

    private function fields(Request $request, bool $partial): array
    {
        return $this->validateFields($request, [
            'horse_id' => ['required', 'string', 'exists:horses,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', self::TIME],
            'end_time' => ['required', self::TIME],
            'active' => ['boolean'],
        ], $partial);
    }
}
