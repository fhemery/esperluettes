<?php

namespace App\Domains\Notification\Private\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Domains\Notification\Private\Services\NotificationService;
use App\Domains\Notification\Private\ViewModels\NotificationPageViewModel;

class NotificationController
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $rows = $this->notifications->listForUser($userId, 20, 0);
        $page = NotificationPageViewModel::fromRows($rows);
        return response(view('notification::pages.index', compact('page')));
    }

    public function markAsRead(int $notificationId): Response
    {
        $userId = (int) Auth::id();
        // Auth middleware ensures guest -> 401 for JSON requests
        $this->notifications->markAsRead($userId, (int) $notificationId);
        return response()->noContent();
    }

    public function markAsUnread(int $notificationId): Response
    {
        $userId = (int) Auth::id();
        $this->notifications->markAsUnread($userId, (int) $notificationId);
        return response()->noContent();
    }

    public function markAllAsRead(): Response
    {
        $userId = (int) Auth::id();
        $this->notifications->markAllAsRead($userId);
        return response()->noContent();
    }
}
