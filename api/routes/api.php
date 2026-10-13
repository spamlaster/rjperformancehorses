<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// Public (replaces the anon RPCs and public reads).
Route::middleware('throttle:public')->group(function () {
    Route::get('/lesson-types', [PublicController::class, 'lessonTypes']);
    Route::get('/config', [PublicController::class, 'config']);
    Route::get('/availability/slots', [PublicController::class, 'slots']);
});
Route::post('/bookings/guest', [PublicController::class, 'createGuestBooking'])->middleware('throttle:guest-booking');

// Admin session auth (Sanctum SPA cookies; CSRF cookie at /api/sanctum/csrf-cookie).
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Admin (replaces the is_admin() RLS policies).
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/lesson-types', [Admin\LessonTypeController::class, 'index']);
    Route::patch('/lesson-types/{lessonType}', [Admin\LessonTypeController::class, 'update']);
    Route::apiResource('horses', Admin\HorseController::class)->except('show');
    Route::apiResource('customers', Admin\CustomerController::class)->only('index', 'store', 'update');
    Route::apiResource('riders', Admin\RiderController::class)->except('show');
    Route::get('/bookings', [Admin\BookingController::class, 'index']);
    Route::apiResource('instructor-availability', Admin\InstructorAvailabilityController::class)
        ->except('show')->parameters(['instructor-availability' => 'instructorAvailability']);
    Route::apiResource('horse-availability', Admin\HorseAvailabilityController::class)
        ->except('show')->parameters(['horse-availability' => 'horseAvailability']);
    Route::apiResource('blocked-times', Admin\BlockedTimeController::class)
        ->only('index', 'store', 'destroy')->parameters(['blocked-times' => 'blockedTime']);
    Route::get('/recurring-lessons', [Admin\RecurringLessonController::class, 'index']);
    Route::patch('/config', [Admin\ConfigController::class, 'update']);
});
