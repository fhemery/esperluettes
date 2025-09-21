<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserReactivated;
use Illuminate\Support\Facades\DB;

class UserActivationService
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}
    /**
     * Activate a user
     */
    public function activateUser(User $user): bool
    {
        if ($user->isActive()) {
            return true; // Already active
        }

        $user->activate();
        // Verify persisted state and emit event
        if ($user->fresh()->isActive()) {
            $this->eventBus->emit(new UserReactivated(
                userId: (int) $user->id,
            ));
        }
        
        return true;
    }

    /**
     * Deactivate a user and terminate all their sessions
     */
    public function deactivateUser(User $user): bool
    {
        if (!$user->isActive()) {
            return true; // Already deactivated
        }

        $user->deactivate();
        
        // Terminate all sessions for this user
        $this->terminateUserSessions($user);
        
        // Verify persisted state and emit event
        if (!$user->fresh()->isActive()) {
            $this->eventBus->emit(new UserDeactivated(
                userId: (int) $user->id,
            ));
        }
        
        return true;
    }

    /**
     * Check if a user can login (is active)
     */
    public function canUserLogin(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Terminate all sessions for a specific user
     */
    private function terminateUserSessions(User $user): void
    {
        // Delete all sessions for this user from the sessions table
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
    }
}
