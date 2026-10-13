<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Sanctum SPA (cookie session) login for admins. */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        $user = Auth::guard('web')->user();
        if (! $user->isAdmin()) {
            $this->endSession($request);

            return response()->json(['message' => 'Access denied. This account is not authorized as an administrator.'], 403);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json($this->profile($user));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->endSession($request);

        return response()->json(['success' => true]);
    }

    private function endSession(Request $request): void
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    private function profile(User $user): array
    {
        return [
            'user' => ['id' => (string) $user->id, 'email' => $user->email, 'name' => $user->name],
            'is_admin' => $user->isAdmin(),
            'admin_profile' => $user->isAdmin()
                ? ['id' => (string) $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role]
                : null,
        ];
    }
}
