<?php

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\UserDeleted;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('AuthPublicApi deleteUserById', function () {
    it('should throw AuthorizationException when caller is not admin or tech-admin', function () {
        $caller = alice($this, roles: [Roles::USER]);
        $target = bob($this, roles: [Roles::USER]);

        $this->actingAs($caller);
        $api = app(AuthPublicApi::class);

        expect(fn () => $api->deleteUserById($target->id))
            ->toThrow(AuthorizationException::class);
    });

    it('should delete target user and emit event when caller is admin', function () {
        /** @var User $admin */
        $admin = admin($this);
        /** @var User $target */
        $target = alice($this, roles: [Roles::USER]);

        $this->actingAs($admin);
        $api = app(AuthPublicApi::class);

        $api->deleteUserById($target->id);

        expect(User::query()->find($target->id))->toBeNull();

        /** @var UserDeleted|null $event */
        $event = latestEventOf(UserDeleted::name(), UserDeleted::class);
        expect($event)->not->toBeNull();
        expect($event->userId)->toBe($target->id);
    });

    it('should delete target user when caller is tech-admin', function () {
        $techAdmin = techAdmin($this);
        $target = bob($this, roles: [Roles::USER]);

        $this->actingAs($techAdmin);
        $api = app(AuthPublicApi::class);

        $api->deleteUserById($target->id);

        expect(User::query()->find($target->id))->toBeNull();
    });
});
