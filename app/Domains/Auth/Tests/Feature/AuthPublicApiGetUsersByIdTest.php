<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AuthPublicApi::getUsersById', function () {

    it('should return user data for given user ids', function () {
        $alice = alice($this, ['email'=> 'alice@example.com'], roles: [Roles::USER]);
        $bob = bob($this, ['email'=> 'bob@example.com'], roles: [Roles::USER_CONFIRMED]);
        
        // Deactivate bob to test different states
        $bob->deactivate();

        $api = app(AuthPublicApi::class);

        $users = $api->getUsersById([$alice->id, $bob->id]);
        
        expect($users)->toBeArray();
        expect($users[$alice->id])->toBeArray();
        expect($users[$alice->id]['email'])->toBe('alice@example.com');
        expect($users[$alice->id]['isActive'])->toBeTrue();
        
        expect($users[$bob->id])->toBeArray();
        expect($users[$bob->id]['email'])->toBe('bob@example.com');
        expect($users[$bob->id]['isActive'])->toBeFalse();
    });

    it('should return empty data for non-existent user ids', function () {
        $api = app(AuthPublicApi::class);

        $users = $api->getUsersById([999, 1000]);
        
        expect($users)->toBeArray();
        expect($users[999])->toBeArray();
        expect($users[999]['email'])->toBe('');
        expect($users[999]['isActive'])->toBeFalse();
        
        expect($users[1000])->toBeArray();
        expect($users[1000]['email'])->toBe('');
        expect($users[1000]['isActive'])->toBeFalse();
    });

    it('should return empty array for empty input', function () {
        $api = app(AuthPublicApi::class);

        $users = $api->getUsersById([]);
        
        expect($users)->toBeArray()->toBeEmpty();
    });

    it('should handle mixed existing and non-existent user ids', function () {
        $alice = alice($this, ['email'=> 'alice@example.com'], roles: [Roles::USER]);
        
        $api = app(AuthPublicApi::class);

        $users = $api->getUsersById([$alice->id, 999]);
        
        expect($users)->toBeArray();
        expect($users[$alice->id])->toBeArray();
        expect($users[$alice->id]['email'])->toBe('alice@example.com');
        expect($users[$alice->id]['isActive'])->toBeTrue();
        
        expect($users[999])->toBeArray();
        expect($users[999]['email'])->toBe('');
        expect($users[999]['isActive'])->toBeFalse();
    });

    it('should return correct active status for activated users', function () {
        $user = alice($this, ['email'=> 'user@example.com'], roles: [Roles::USER]);
        
        // Ensure user is active
        $user->activate();

        $api = app(AuthPublicApi::class);

        $users = $api->getUsersById([$user->id]);
        
        expect($users[$user->id]['isActive'])->toBeTrue();
    });

    it('should return correct inactive status for deactivated users', function () {
        $user = alice($this, ['email'=> 'user@example.com'], roles: [Roles::USER]);
        
        // Deactivate the user
        $user->deactivate();

        $api = app(AuthPublicApi::class);

        $users = $api->getUsersById([$user->id]);
        
        expect($users[$user->id]['isActive'])->toBeFalse();
    });
});
