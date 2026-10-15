<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusinessConfig;
use App\Models\Horse;
use App\Models\HorseRecurringAvailability;
use App\Models\InstructorAvailability;
use App\Models\LessonType;
use App\Models\Waiver;
use App\Services\WaiverService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetupController extends AdminController
{
    public function index(): JsonResponse
    {
        $waiver = WaiverService::current();

        return response()->json([
            'business_timezone' => BusinessConfig::timezone(),
            'timezones' => timezone_identifiers_list(),
            'waiver' => $waiver ? WaiverService::document($waiver) : null,
            'lesson_count' => LessonType::query()->where('active', true)->count(),
            'horse_count' => Horse::query()->where('active', true)->whereNull('deleted_at')->count(),
            'instructor_hours' => InstructorAvailability::query()->where('active', true)->exists(),
            'horse_hours' => HorseRecurringAvailability::query()->where('active', true)
                ->whereHas('horse', fn ($q) => $q->where('active', true)->whereNull('deleted_at'))->exists(),
        ])->header('Cache-Control', 'no-store');
    }

    public function storeWaiver(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:60000'],
        ]);

        return DB::transaction(function () use ($data): JsonResponse {
            BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->firstOrFail();
            $version = ((int) Waiver::query()->max('version')) + 1;
            Waiver::query()->where('active', true)->update(['active' => false]);
            $waiver = Waiver::create([
                ...$data, 'version' => $version, 'active' => true,
                'effective_date' => CarbonImmutable::now(BusinessConfig::timezone())->toDateString(),
            ]);

            return response()->json(WaiverService::document($waiver), 201)->header('Cache-Control', 'no-store');
        });
    }
}
