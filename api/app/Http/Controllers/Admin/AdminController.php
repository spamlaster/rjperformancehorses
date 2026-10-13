<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class AdminController extends Controller
{
    protected const TIME = 'regex:/^\d{2}:\d{2}(:\d{2})?$/';

    /**
     * Validate a create (all required fields) or a partial update (only the
     * fields sent, like the old Supabase .update() calls).
     *
     * @param  array<string, array<int, mixed>>  $rules  rules for a create
     */
    protected function validateFields(Request $request, array $rules, bool $partial): array
    {
        if ($partial) {
            $rules = array_map(fn ($r) => ['sometimes', ...array_diff($r, ['required'])], $rules);
        }

        return $request->validate($rules);
    }
}
