<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusinessConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends AdminController
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['business_timezone' => ['required', 'timezone:all']]);
        BusinessConfig::query()->whereKey('business_timezone')->update(['config_value' => $data['business_timezone'], 'updated_at' => now()]);

        return response()->json(['business_timezone' => $data['business_timezone']]);
    }
}
