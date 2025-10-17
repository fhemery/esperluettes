<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    public function deleteUser(User $user, ?Request $request = null): void
    {
        if ($request !== null) {
            /** @var Authenticatable|null $current */
            $current = $request->user();
            if ($current && (int) $current->getAuthIdentifier() === (int) $user->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        // Terminate all sessions for this user (works for admin-triggered deletions too)
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();

        $this->eventBus->emit(new UserDeleted(userId: (int) $user->id));
    }
}
