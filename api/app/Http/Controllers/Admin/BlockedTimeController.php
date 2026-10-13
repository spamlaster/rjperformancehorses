<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlockedTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockedTimeController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(BlockedTime::query()->orderBy('date')->orderBy('start_time')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateFields($request, [
            'horse_id' => ['nullable', 'string', 'exists:horses,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', self::TIME],
            'end_time' => ['required', self::TIME],
            'reason' => ['nullable', 'string', 'max:255'],
        ], partial: false);

        return response()->json(BlockedTime::create($data)->refresh(), 201);
    }

    public function destroy(BlockedTime $blockedTime): JsonResponse
    {
        $blockedTime->delete();

        return response()->json(['success' => true]);
    }
}
