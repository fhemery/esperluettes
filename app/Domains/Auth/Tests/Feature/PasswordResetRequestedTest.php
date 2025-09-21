<?php

use App\Domains\Auth\Public\Events\PasswordResetRequested;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Password reset link request event', function () {
    it('emits and persists Auth.PasswordResetRequested when a reset link is requested', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $this->from('/forgot-password')->post(route('password.email'), [
            'email' => $user->email,
        ])->assertRedirect('/forgot-password');

        /* @var PasswordResetRequested $dto */
        $dto = latestEventOf(PasswordResetRequested::name(), PasswordResetRequested::class);
        expect($dto)->not->toBeNull();
        expect($dto->email)->toBe($user->email);
        expect($dto->userId)->toBe($user->id);
    });
});
