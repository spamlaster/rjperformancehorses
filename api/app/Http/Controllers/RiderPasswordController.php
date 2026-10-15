<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RiderPasswordController extends Controller
{
    public function email(Request $request): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($input['email']));
        $user = User::query()->where('email', $email)->whereNull('role')->where('is_active', true)->first();
        if ($user) {
            Password::sendResetLink(['email' => $email, 'role' => null, 'is_active' => true]);
        }

        return response()->json(['message' => 'If a rider account exists for that email, we have sent a password reset link.'])->header('Cache-Control', 'no-store');
    }

    public function reset(Request $request): JsonResponse
    {
        $input = $request->validate([
            'email' => ['required', 'email'], 'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        $input['email'] = mb_strtolower(trim($input['email']));
        $status = Password::reset([...$input, 'role' => null, 'is_active' => true], function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password, 'remember_token' => Str::random(60),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or expired. Request a new link.']);
        }

        return response()->json(['message' => 'Your password has been reset. Sign in with your new password.']);
    }
}
