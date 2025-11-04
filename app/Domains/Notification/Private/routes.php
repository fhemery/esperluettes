<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Notification\Private\Controllers\NotificationController;

Route::middleware(['web', 'auth', 'role:'.Roles::USER.','.Roles::USER_CONFIRMED])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/load-more', [NotificationController::class, 'loadMore'])->name('notifications.loadMore');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
    Route::post('/notifications/{notificationId}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.markUnread');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
});
