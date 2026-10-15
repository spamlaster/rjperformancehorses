<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuestReservationController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RiderAccountController;
use Illuminate\Support\Facades\Route;

// Public (replaces the anon RPCs and public reads).
Route::middleware('throttle:public')->group(function () {
    Route::get('/lesson-types', [PublicController::class, 'lessonTypes']);
    Route::get('/config', [PublicController::class, 'config']);
    Route::get('/waiver', [PublicController::class, 'waiver']);
    Route::get('/availability/slots', [PublicController::class, 'slots']);
});
Route::post('/bookings/guest', [PublicController::class, 'createGuestBooking'])->middleware(['auth:sanctum', 'throttle:guest-booking']);

Route::middleware('throttle:reservation-management')->group(function () {
    Route::post('/reservations/manage', [GuestReservationController::class, 'show']);
    Route::post('/reservations/cancel', [GuestReservationController::class, 'cancel']);
    Route::post('/reservations/skip', [GuestReservationController::class, 'skip']);
});

Route::middleware('auth:sanctum')->prefix('rider/account')->group(function () {
    Route::get('/', [RiderAccountController::class, 'show']);
    Route::patch('/', [RiderAccountController::class, 'update']);
    Route::post('/riders', [RiderAccountController::class, 'createRider']);
    Route::post('/reservations/{id}/cancel', [RiderAccountController::class, 'cancel']);
    Route::post('/reservations/{id}/skip', [RiderAccountController::class, 'skip']);
});

// Admin session auth (Sanctum SPA cookies; CSRF cookie at /api/sanctum/csrf-cookie).
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Admin (replaces the is_admin() RLS policies).
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::patch('/password', [AuthController::class, 'updatePassword'])->middleware('throttle:password-update');
    Route::get('/setup', [Admin\SetupController::class, 'index']);
    Route::post('/waivers', [Admin\SetupController::class, 'storeWaiver']);
    Route::get('/lesson-types', [Admin\LessonTypeController::class, 'index']);
    Route::post('/lesson-types', [Admin\LessonTypeController::class, 'store']);
    Route::patch('/lesson-types/{lessonType}', [Admin\LessonTypeController::class, 'update']);
    Route::apiResource('horses', Admin\HorseController::class)->except('show');
    Route::apiResource('customers', Admin\CustomerController::class)->only('index', 'store', 'update');
    Route::apiResource('riders', Admin\RiderController::class)->except('show');
    Route::get('/bookings', [Admin\BookingController::class, 'index']);
    Route::post('/bookings', [Admin\BookingController::class, 'store']);
    Route::get('/bookings/{booking}/waivers', [Admin\WaiverController::class, 'booking']);
    Route::get('/recurring-lessons/{recurringLesson}/waivers', [Admin\WaiverController::class, 'recurring']);
    Route::post('/bookings/{booking}/cancel', [Admin\BookingController::class, 'cancel']);
    Route::apiResource('instructor-availability', Admin\InstructorAvailabilityController::class)
        ->except('show')->parameters(['instructor-availability' => 'instructorAvailability']);
    Route::apiResource('horse-availability', Admin\HorseAvailabilityController::class)
        ->except('show')->parameters(['horse-availability' => 'horseAvailability']);
    Route::apiResource('blocked-times', Admin\BlockedTimeController::class)
        ->only('index', 'store', 'destroy')->parameters(['blocked-times' => 'blockedTime']);
    Route::get('/recurring-lessons', [Admin\RecurringLessonController::class, 'index']);
    Route::post('/recurring-lessons/{recurringLesson}/cancel', [Admin\RecurringLessonController::class, 'cancel']);
    Route::patch('/config', [Admin\ConfigController::class, 'update']);
});
