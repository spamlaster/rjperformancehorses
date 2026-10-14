<?php

namespace App\Http\Controllers;

use App\Mail\RiderSignInCode;
use App\Models\User;
use App\Services\RiderAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class RiderSignInController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft', 'apple'];

    private function enabled(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true)
            && filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret"))
            && filled(config("services.{$provider}.redirect"));
    }

    public function options(Request $request): JsonResponse
    {
        return response()->json([
            'providers' => collect(self::PROVIDERS)->mapWithKeys(fn ($provider) => [$provider => $this->enabled($provider)]),
            'pending_email' => $request->session()->get('rider_oauth.email'),
        ])->header('Cache-Control', 'no-store');
    }

    private function sendCode(string $email): void
    {
        $code = (string) random_int(100000, 999999);
        DB::table('rider_sign_in_codes')->updateOrInsert(['email' => $email], [
            'code_hash' => hash_hmac('sha256', $code, config('app.key')),
            'expires_at' => now()->addMinutes(10), 'attempts' => 0, 'created_at' => now(),
        ]);
        Mail::to($email)->send(new RiderSignInCode($code));
    }

    public function requestCode(Request $request): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($input['email']));
        $pending = $request->session()->get('rider_oauth');
        if ($pending && $pending['email'] !== $email) {
            $request->session()->forget('rider_oauth');
        }
        $this->sendCode($email);

        return response()->json(['message' => 'Check your inbox for a sign-in code.']);
    }

    public function verifyCode(Request $request, RiderAccountService $accounts): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $email = mb_strtolower(trim($input['email']));
        $valid = DB::transaction(function () use ($email, $input) {
            $code = DB::table('rider_sign_in_codes')->where('email', $email)->lockForUpdate()->first();
            if (! $code || $code->expires_at <= now()->format('Y-m-d H:i:s') || $code->attempts >= 5) {
                return false;
            }
            if (! hash_equals($code->code_hash, hash_hmac('sha256', $input['code'], config('app.key')))) {
                DB::table('rider_sign_in_codes')->where('email', $email)->increment('attempts');

                return false;
            }
            DB::table('rider_sign_in_codes')->where('email', $email)->delete();

            return true;
        });
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'That code is invalid or expired. Request a new code and try again.']);
        }
        $pending = $request->session()->pull('rider_oauth');
        $identity = $pending && $pending['email'] === $email && $pending['expires_at'] > now()->timestamp ? $pending : null;
        $user = $accounts->signIn($email, $identity);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
    }

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless($this->enabled($provider), 404);
        $driver = Socialite::driver($provider);
        if ($provider === 'apple') {
            return $driver->stateless()->cookieNonce()->redirect();
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->enabled($provider), 404);
        try {
            $driver = Socialite::driver($provider);
            $profile = $provider === 'apple' ? $driver->stateless()->cookieNonce()->user() : $driver->user();
            $subject = (string) $profile->getId();
            if ($subject === '' || strlen($subject) > 255) {
                throw new \RuntimeException('Missing provider identity');
            }
            $identity = DB::table('social_identities')->where('provider', $provider)->where('subject', $subject)->first();
            if ($identity) {
                $user = User::query()->findOrFail($identity->user_id);
                abort_unless($user->is_active && $user->role === null, 403);
                Auth::guard('web')->login($user);
                $request->session()->regenerate();

                return redirect('/rider');
            }
            $email = mb_strtolower(trim((string) $profile->getEmail()));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect('/rider/sign-in?error=email_required');
            }
            // A provider's email claim alone never grants access to an existing account.
            $request->session()->put('rider_oauth', [
                'provider' => $provider, 'subject' => $subject, 'email' => $email,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect('/rider/sign-in?verify_email=1');
        } catch (\Throwable) {
            return redirect('/rider/sign-in?error=provider');
        }
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }
}
