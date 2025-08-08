<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserActivationService
{
    /**
     * Activate a user
     */
    public function activateUser(User $user): bool
    {
        if ($user->isActive()) {
            return true; // Already active
        }

        $user->activate();
        
        $this->logActivationChange($user, 'activated');
        
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
        
        $this->logActivationChange($user, 'deactivated');
        
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

    /**
     * Log activation/deactivation changes for audit purposes
     */
    private function logActivationChange(User $user, string $action): void
    {
        $adminUser = Auth::user();
        $adminName = $adminUser ? $adminUser->name : 'System';
        $adminId = $adminUser ? $adminUser->id : null;

        Log::info("User {$action}", [
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'target_user_email' => $user->email,
            'admin_user_id' => $adminId,
            'admin_user_name' => $adminName,
            'action' => $action,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
