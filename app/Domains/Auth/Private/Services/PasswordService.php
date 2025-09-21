<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Public\Events\PasswordChanged;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordService
{
    public function __construct(private readonly EventBus $eventBus)
    {
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        $this->eventBus->emit(new PasswordChanged(userId: $user->id));
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();

        $this->eventBus->emit(new PasswordChanged(userId: $user->id));
    }
}
