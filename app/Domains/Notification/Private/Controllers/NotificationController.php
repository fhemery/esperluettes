<?php

namespace App\Domains\Notification\Private\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Domains\Notification\Private\Services\NotificationService;
use App\Domains\Notification\Private\ViewModels\NotificationPageViewModel;
use App\Domains\Shared\Contracts\ProfilePublicApi;

class NotificationController
{
    public function __construct(
        private NotificationService $notifications,
        private ProfilePublicApi $profiles,
    )
    {
    }

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $rows = $this->notifications->listForUser($userId, 20, 0);

        // Prepare actor profiles to let the ViewModel bind avatar URLs
        $actorIds = array_values(array_unique(array_filter(array_map(
            fn ($r) => isset($r['source_user_id']) ? (int) $r['source_user_id'] : null,
            $rows
        ))));

        $profilesById = $this->profiles->getPublicProfiles($actorIds);

        $page = NotificationPageViewModel::fromRows($rows, $profilesById);
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
