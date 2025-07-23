<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Api\ZoomController;
use App\Http\Controllers\Api\MeetingLocationController;
use App\Http\Controllers\Api\MeetingController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Email Verification - This route is public as it's accessed from the email link
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    // Core Meeting Management
    Route::apiResource('meetings', MeetingController::class)->only(['index', 'store', 'show', 'destroy', 'update']);
    Route::apiResource('meeting-locations', MeetingLocationController::class);

    // Zoom Specific Routes
    Route::prefix('zoom')->group(function () {
        Route::post('/auth', [ZoomController::class, 'authenticate']);
        Route::post('/meetings', [ZoomController::class, 'createMeeting']);
        Route::patch('/meetings', [ZoomController::class, 'updateMeeting']);
        Route::delete('/meetings', [ZoomController::class, 'deleteMeeting']);
        Route::get('/meetings', [ZoomController::class, 'getMeeting']);
        Route::get('/meetings/{meetingUuid}/summary', [ZoomController::class, 'getMeetingSummary']);
        Route::get('/past_meetings', [ZoomController::class, 'getPastMeetingDetails']);
    });

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Resend Verification Email
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});
