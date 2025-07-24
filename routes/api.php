<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Api\ZoomController;
use App\Http\Controllers\Api\MeetingLocationController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

// Email Verification - This route is public as it's accessed from the email link
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    // User and Auth
    Route::post('/register', [AuthController::class, 'register'])->middleware('permission:manage users');
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Core Meeting Management
    Route::apiResource('meetings', MeetingController::class)->except(['destroy'])
        ->middleware('permission:manage meetings');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])
        ->middleware('permission:delete meetings');
    Route::apiResource('meeting-locations', MeetingLocationController::class)->middleware('permission:manage meetings');

    // Zoom Specific Routes
    Route::prefix('zoom')->middleware('permission:manage meetings')->group(function () {
        Route::post('/auth', [ZoomController::class, 'authenticate']);
        Route::post('/meetings', [ZoomController::class, 'createMeeting']);
        Route::patch('/meetings', [ZoomController::class, 'updateMeeting']);
        Route::delete('/meetings', [ZoomController::class, 'deleteMeeting']);
        Route::get('/meetings', [ZoomController::class, 'getMeeting']);
        Route::get('/meetings/{meetingUuid}/summary', [ZoomController::class, 'getMeetingSummary']);
        Route::get('/past_meetings', [ZoomController::class, 'getPastMeetingDetails']);
    });

    // User Profile Management
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    Route::post('/user/change-name', [UserController::class, 'changeName']);
    Route::post('/user/change-email', [UserController::class, 'changeEmail']);

    // Role and Permission Management
    Route::apiResource('roles', RoleController::class)->middleware('permission:manage roles');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermission'])->middleware('permission:manage roles');
    Route::delete('/roles/{role}/permissions', [RoleController::class, 'revokePermission'])->middleware('permission:manage roles');
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:manage roles');


    // Resend Verification Email
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});
