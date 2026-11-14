<?php

use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlagController as AdminFlagController;
use App\Http\Controllers\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Admin\NoticeController as AdminNoticeController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:20,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Items and discovery
    Route::get('/items/recent-lost', [ItemController::class, 'recentLost']);
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::put('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
    Route::get('/categories', [ItemController::class, 'categories']);
    Route::get('/dashboard/stats', [ItemController::class, 'stats']);

    // My reports
    Route::get('/my-reports/resolved', [ItemController::class, 'resolvedReports']);
    Route::get('/my-reports', [ItemController::class, 'myReports']);

    // Claims
    Route::post('/items/{item}/claims', [ClaimController::class, 'store']);
    Route::get('/claims/{claim}', [ClaimController::class, 'show']);
    Route::middleware('staff')->group(function () {
        Route::get('/claims', [ClaimController::class, 'index']);
        Route::patch('/claims/{claim}/status', [ClaimController::class, 'updateStatus']);
    });

    // Admin dashboard
    Route::middleware('staff')->prefix('admin')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::put('users/{user}', [AdminUserController::class, 'update']);
        Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);

        Route::get('items', [AdminItemController::class, 'index']);
        Route::put('items/{item}', [AdminItemController::class, 'update']);
        Route::patch('items/{item}/status', [AdminItemController::class, 'updateStatus']);
        Route::delete('items/{item}', [AdminItemController::class, 'destroy']);

        Route::get('flags', [AdminFlagController::class, 'index']);
        Route::post('flags', [AdminFlagController::class, 'store']);
        Route::patch('flags/{flag}', [AdminFlagController::class, 'update']);

        Route::get('activity', [AdminActivityController::class, 'index']);
        Route::get('settings', [AdminSettingController::class, 'index']);
        Route::put('settings', [AdminSettingController::class, 'update']);
        Route::post('notices', [AdminNoticeController::class, 'store']);
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::get('/profile/activity', [ProfileController::class, 'activity']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
});
