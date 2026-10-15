<?php

use App\Http\Controllers\RiderPasswordController;
use App\Http\Controllers\RiderSignInController;
use Illuminate\Support\Facades\Route;

// OAuth navigation and email codes use ordinary cookie sessions, including
// top-level redirects which do not carry a SPA Origin or Referer.
Route::prefix('api/rider/auth')->group(function () {
    Route::get('/options', [RiderSignInController::class, 'options'])->middleware('throttle:public');
    Route::post('/forgot-password', [RiderPasswordController::class, 'email'])->middleware('throttle:rider-email');
    Route::post('/reset-password', [RiderPasswordController::class, 'reset'])->middleware('throttle:rider-verify');
    Route::post('/login', [RiderSignInController::class, 'login'])->middleware('throttle:login');
    Route::post('/email', [RiderSignInController::class, 'requestCode'])->middleware('throttle:rider-email');
    Route::post('/verify', [RiderSignInController::class, 'verifyCode'])->middleware('throttle:rider-verify');
    Route::post('/logout', [RiderSignInController::class, 'logout'])->middleware('auth');
});
Route::get('/api/rider/oauth/{provider}/redirect', [RiderSignInController::class, 'redirect'])->middleware('throttle:public');
Route::match(['get', 'post'], '/api/rider/oauth/{provider}/callback', [RiderSignInController::class, 'callback'])->middleware('throttle:public');
