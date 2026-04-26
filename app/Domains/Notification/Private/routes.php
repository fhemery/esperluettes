<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Notification\Private\Controllers\NotificationController;
use App\Domains\Notification\Private\Controllers\NotificationPreferencesController;

Route::middleware(['web', 'auth', 'compliant', 'role:'.Roles::USER.','.Roles::USER_CONFIRMED])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/load-more', [NotificationController::class, 'loadMore'])->name('notifications.loadMore');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
    Route::post('/notifications/{notificationId}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.markUnread');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');

    Route::prefix('notifications/preferences')->name('notification.preferences.')->group(function () {
        Route::post('/',       [NotificationPreferencesController::class, 'save'])->name('save');
        Route::put('/',        [NotificationPreferencesController::class, 'bulkUpdate'])->name('bulk');
        Route::put('/{type}',  [NotificationPreferencesController::class, 'update'])->name('update');
    });
});
