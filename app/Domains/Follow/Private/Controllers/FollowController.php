<?php

namespace App\Domains\Follow\Private\Controllers;

use App\Domains\Follow\Private\Services\FollowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController
{
    public function __construct(private FollowService $service) {}

    public function follow(Request $request, int $userId): RedirectResponse
    {
        $currentUserId = (int) Auth::id();

        if ($currentUserId === $userId) {
            return back();
        }

        $this->service->follow($currentUserId, $userId);

        return back();
    }

    public function unfollow(Request $request, int $userId): RedirectResponse
    {
        $currentUserId = (int) Auth::id();

        $this->service->unfollow($currentUserId, $userId);

        return back();
    }
}
