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
use Illuminate\Support\Facades\Hash;
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
        $user = $request->user();

        return response()->json([
            'signed_in' => $user !== null,
            'signed_in_as_rider' => $user && $user->is_active && $user->role === null && $user->email_verified_at !== null,
            'providers' => collect(self::PROVIDERS)->mapWithKeys(fn ($provider) => [$provider => $this->enabled($provider)]),
            'return_to' => $request->session()->get('rider_return_to', '/rider'),
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

    public function login(Request $request): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::query()->where('email', mb_strtolower(trim($input['email'])))->first();
        if (! $user || ! $user->is_active || $user->role !== null || ! $user->password
            || ! Hash::check($input['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email or password is incorrect. Use Reset password if you need a new password.']);
        }
        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages(['email' => 'Verify your email when creating your account before signing in.']);
        }
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
    }

    public function requestCode(Request $request): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($input['email']));
        $oauth = $request->session()->get('rider_oauth');
        $linking = $oauth && $oauth['email'] === $email && $oauth['expires_at'] > now()->timestamp;
        $existing = User::query()->where('email', $email)->first();
        if ($existing && (! $existing->is_active || $existing->role !== null)) {
            throw ValidationException::withMessages(['email' => 'Use the admin sign-in for staff accounts, or contact Rylie if your account is inactive.']);
        }
        if (! $linking) {
            $password = $request->validate(['password' => ['required', 'string', 'min:8', 'max:255', 'confirmed']])['password'];
            if ($existing) {
                throw ValidationException::withMessages(['email' => 'An account already exists. Sign in or use Reset password.']);
            }
            $request->session()->put('rider_registration', [
                'email' => $email, 'password_hash' => Hash::make($password), 'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
            $request->session()->forget('rider_oauth');
        }
        if ($linking) {
            $request->session()->forget('rider_registration');
        }
        $this->sendCode($email);

        return response()->json(['message' => 'Check your inbox to verify your email.']);
    }

    public function verifyCode(Request $request, RiderAccountService $accounts): JsonResponse
    {
        $input = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $email = mb_strtolower(trim($input['email']));
        $registration = $request->session()->get('rider_registration');
        $oauth = $request->session()->get('rider_oauth');
        $context = $registration ?? $oauth;
        if (! $context || $context['email'] !== $email || $context['expires_at'] <= now()->timestamp) {
            throw ValidationException::withMessages(['code' => 'Start account setup again before verifying your email.']);
        }
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
        $user = DB::transaction(function () use ($email, $identity, $registration, $accounts) {
            $existing = User::query()->where('email', $email)->lockForUpdate()->first();
            if ($registration && $existing?->password && $existing->email_verified_at !== null) {
                throw ValidationException::withMessages(['email' => 'This account already has a password. Sign in instead.']);
            }
            $user = $accounts->signIn($email, $identity);
            if ($registration) {
                $user->update(['password' => $registration['password_hash']]);
            }

            return $user;
        });
        $request->session()->forget('rider_registration');
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->enabled($provider), 404);
        $request->session()->put('rider_return_to', $request->query('returnTo') === '/book' ? '/book' : '/rider');
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

                return redirect($request->session()->pull('rider_return_to', '/rider') === '/book' ? '/book' : '/rider');
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
