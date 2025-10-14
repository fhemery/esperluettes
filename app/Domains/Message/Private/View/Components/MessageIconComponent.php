<?php

declare(strict_types=1);

namespace App\Domains\Message\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Message\Private\Services\UnreadCounterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class MessageIconComponent extends Component
{
    public int $unreadCount = 0;
    public bool $shouldDisplay = false;

    public function __construct(
        private UnreadCounterService $unreadCounter,
        private AuthPublicApi $authPublicApi
    ) {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();

        // Display icon if:
        // - User is admin (even with 0 messages)
        // - OR user has any messages
        $isAdmin = $this->authPublicApi->hasAnyRole([Roles::ADMIN]);
        $hasMessages = $this->unreadCounter->hasAnyMessages($userId);

        $this->shouldDisplay = $isAdmin || $hasMessages;
        
        if ($this->shouldDisplay) {
            $this->unreadCount = $this->unreadCounter->getUnreadCount($userId);
        }
    }

    public function render()
    {
        return view('message::components.message-icon');
    }
}
