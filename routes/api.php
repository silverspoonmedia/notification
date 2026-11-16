<?php

use App\Domain\Notification\Http\Controllers\API\V1\AdminNotificationAudienceController;
use App\Domain\Notification\Http\Controllers\API\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'permission:view settings'])
    ->prefix('admin/notification-audiences')
    ->name('admin.notification-audiences.')
    ->group(function (): void {
        Route::get('/', [AdminNotificationAudienceController::class, 'index'])->name('index');
        Route::get('{type}', [AdminNotificationAudienceController::class, 'show'])->name('show');

        Route::middleware('permission:manage settings')->group(function (): void {
            Route::put('{type}', [AdminNotificationAudienceController::class, 'update'])->name('update');
        });
    });

Route::middleware(['auth:api', 'permission:view notification'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead'])
            ->whereUuid('notification')
            ->name('read');
    });
