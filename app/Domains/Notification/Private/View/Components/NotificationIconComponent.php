<?php

declare(strict_types=1);

namespace App\Domains\Notification\Private\View\Components;

use App\Domains\Notification\Private\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class NotificationIconComponent extends Component
{
    public int $unreadCount = 0;
    public bool $shouldDisplay = false;

    public function __construct(private NotificationService $notificationService)
    {
        if (!Auth::check()) {
            return;
        }

        $this->shouldDisplay = true;
        $this->unreadCount = $this->notificationService->getUnreadCount((int) Auth::id());
    }

    public function render()
    {
        return view('notification::components.notification-icon');
    }
}
