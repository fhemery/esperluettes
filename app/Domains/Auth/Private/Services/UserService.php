<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserReactivated;

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

    /**
     * Activate a user. Emits UserReactivated when state changes.
     */
    public function activateUser(User $user): bool
    {
        if ($user->isActive()) {
            return true; // Already active
        }

        $user->activate();

        if ($user->fresh()->isActive()) {
            $this->eventBus->emit(new UserReactivated(userId: (int) $user->id));
        }

        return true;
    }

    /**
     * Deactivate a user, terminate all sessions. Emits UserDeactivated when state changes.
     */
    public function deactivateUser(User $user): bool
    {
        if (! $user->isActive()) {
            return true; // Already deactivated
        }

        $user->deactivate();

        $this->terminateUserSessions($user);

        if (! $user->fresh()->isActive()) {
            $this->eventBus->emit(new UserDeactivated(userId: (int) $user->id));
        }

        return true;
    }

    private function terminateUserSessions(User $user): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Get users by ID with email and active status.
     *
     * @param array<int> $userIds
     * @return array<int,array{email:string,isActive:bool}>
     */
    public function getUsersById(array $userIds): array
    {
        $users = User::query()
            ->whereIn('id', $userIds)
            ->select(['id', 'email', 'is_active'])
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($userIds as $userId) {
            $user = $users->get($userId);
            $result[$userId] = [
                'email' => $user?->email ?? '',
                'isActive' => $user?->is_active ?? false,
            ];
        }

        return $result;
    }
}
