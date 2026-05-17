<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\LogsController;
use App\Http\Controllers\Manager\ReportsController;
use App\Http\Controllers\Manager\ShiftsController;
use App\Http\Controllers\Manager\TeamController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PunchController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes (rate limited)
// Route::middleware('throttle:5,15')->group(function () {
    Route::post('/auth/login',      [AuthController::class, 'login']);
    Route::post('/auth/verify-pin', [AuthController::class, 'verifyPin']);
// });

Route::post('/auth/oauth', [AuthController::class, 'oauth']);

// Authenticated routes
Route::middleware(['auth:sanctum', 'org.isolation'])->group(function () {

    Route::post('/auth/logout',           [AuthController::class, 'logout']);
    Route::post('/auth/set-pin',          [AuthController::class, 'setPin']);
    Route::patch('/auth/change-password', [AuthController::class, 'changePassword']);

    Route::get('/users/me',                    [UserController::class, 'me']);
    Route::patch('/users/me',                  [UserController::class, 'update']);

    Route::get('/sites',          [SiteController::class, 'index']);
    Route::get('/sites/{id}',     [SiteController::class, 'show']);

    Route::get('/notifications',                      [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count',         [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read',           [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all',            [NotificationController::class, 'markAllRead']);

    // Employee-only routes
    Route::middleware('role:employee')->group(function () {
        Route::get('/shifts/me',          [ShiftController::class, 'mine']);
        Route::get('/shifts/me/upcoming', [ShiftController::class, 'upcoming']);

        Route::get('/stats/me',         [StatsController::class, 'monthly']);
        Route::get('/stats/me/weekly',  [StatsController::class, 'weekly']);

        Route::post('/punch/clock-in',   [PunchController::class, 'clockIn']);
        Route::get('/punch/active',      [PunchController::class, 'active']);
        Route::get('/punch/history',     [PunchController::class, 'history']);
        Route::get('/punch/{id}',        [PunchController::class, 'show']);
        Route::post('/punch/break-start',[PunchController::class, 'breakStart']);
        Route::post('/punch/break-end',  [PunchController::class, 'breakEnd']);
        Route::post('/punch/clock-out',  [PunchController::class, 'clockOut']);
    });

    // Manager-only routes
    Route::middleware('role:manager')->prefix('manager')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/team',      [TeamController::class, 'index']);

        Route::get('/logs',             [LogsController::class, 'index']);
        Route::post('/logs/manual-adj', [LogsController::class, 'manualAdj']);

        Route::get('/shifts',          [ShiftsController::class, 'index']);
        Route::post('/shifts',         [ShiftsController::class, 'store']);
        Route::patch('/shifts/{id}',   [ShiftsController::class, 'update']);
        Route::delete('/shifts/{id}',  [ShiftsController::class, 'destroy']);

        Route::get('/reports',             [ReportsController::class, 'index']);
        Route::post('/reports',            [ReportsController::class, 'store']);
        Route::post('/reports/{id}/run',   [ReportsController::class, 'run']);
        Route::delete('/reports/{id}',     [ReportsController::class, 'destroy']);

        Route::get('/users',                      [UserController::class, 'index']);
        Route::post('/users',                     [UserController::class, 'store']);
        Route::get('/users/{id}',                 [UserController::class, 'show']);
        Route::patch('/users/{id}',               [UserController::class, 'updateEmployee']);
        Route::post('/users/{id}/reset-pin',      [UserController::class, 'resetPin']);

        Route::post('/sites',          [SiteController::class, 'store']);
        Route::patch('/sites/{id}',    [SiteController::class, 'update']);
    });
});
