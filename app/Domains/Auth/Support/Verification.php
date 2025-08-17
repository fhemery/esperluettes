<?php

namespace App\Domains\Auth\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

final class Verification
{
    /**
     * Returns true if the user is considered verified.
     * - Guests are not verified
     * - Users implementing MustVerifyEmail must have verified their email
     * - Other user types are considered verified by default
     */
    public static function isVerified(?Authenticatable $user): bool
    {
        if (!$user) {
            return false;
        }

        return !($user instanceof MustVerifyEmail) || $user->hasVerifiedEmail();
    }
}
