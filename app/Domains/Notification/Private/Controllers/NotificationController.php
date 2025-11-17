<?php

namespace App\Domains\Notification\Private\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Domains\Notification\Private\Services\NotificationService;
use App\Domains\Notification\Private\ViewModels\NotificationPageViewModel;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class NotificationController
{
    const PAGE_SIZE = 20;

    public function __construct(
        private NotificationService $notifications,
        private ProfilePublicApi $profiles,
    )
    {
    }

    public function index(): Response
    {
        $userId = (int) Auth::id();
        $rows = $this->notifications->listForUser($userId, self::PAGE_SIZE, 0);

        // Check if there are more notifications beyond this page
        $hasMore = count($this->notifications->listForUser($userId, 1, self::PAGE_SIZE)) > 0;

        // Prepare actor profiles to let the ViewModel bind avatar URLs
        $actorIds = array_values(array_unique(array_filter(array_map(
            fn ($r) => isset($r['source_user_id']) ? (int) $r['source_user_id'] : null,
            $rows
        ))));

        $profilesById = $this->profiles->getPublicProfiles($actorIds);

        $page = NotificationPageViewModel::fromRows($rows, $profilesById, $hasMore);
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

    public function deleteAllRead(): RedirectResponse
    {
        $userId = (int) Auth::id();
        $deletedCount = $this->notifications->deleteAllRead($userId);
        
        return redirect()
            ->route('notifications.index')
            ->with('success', trans_choice('notifications::pages.index.delete_all_read_success', $deletedCount, [
                'count' => $deletedCount
            ]));
    }

    public function loadMore(Request $request): Response
    {
        $userId = (int) Auth::id();
        $offset = (int) $request->query('offset', 0);
        $limit = self::PAGE_SIZE;

        $rows = $this->notifications->listForUser($userId, $limit, $offset);

        // Check if there are more notifications beyond this batch
        $hasMore = count($this->notifications->listForUser($userId, 1, $offset + $limit)) > 0;

        // Prepare actor profiles
        $actorIds = array_values(array_unique(array_filter(array_map(
            fn ($r) => isset($r['source_user_id']) ? (int) $r['source_user_id'] : null,
            $rows
        ))));

        $profilesById = $this->profiles->getPublicProfiles($actorIds);

        $page = NotificationPageViewModel::fromRows($rows, $profilesById);

        // Render partial for each notification
        $html = '';
        foreach ($page->notifications as $notification) {
            $html .= view('notification::components.notification-item', ['notification' => $notification])->render();
        }

        return response($html)
            ->header('X-Has-More', $hasMore ? 'true' : 'false');
    }
}
