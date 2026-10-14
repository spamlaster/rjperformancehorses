<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', Provider::class);
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
        RateLimiter::for('rider-email', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip()),
            Limit::perMinute(3)->by('email:'.mb_strtolower(trim((string) $request->input('email')))),
        ]);
        RateLimiter::for('rider-verify', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Named limiters keep separate counters; plain throttle:N,1 shares one per IP,
        // so browsing dates would use up the booking allowance.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('guest-booking', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('reservation-management', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('password-update', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()?->id));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
