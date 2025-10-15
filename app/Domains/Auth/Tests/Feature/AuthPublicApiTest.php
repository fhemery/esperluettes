<?php

use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AuthPublicApi', function () {

    describe('getRolesByUserIds', function () {
        it('should return roles for given user ids', function () {
            $alice = alice($this, roles: [Roles::USER]);
            $bob = bob($this, roles: [Roles::USER_CONFIRMED]);


            $api = app(AuthPublicApi::class);

            $roles = $api->getRolesByUserIds([$alice->id, $bob->id]);
            expect($roles)->toBeArray();
            expect($roles[$alice->id])->toBeArray();
            expect($roles[$alice->id][0]->slug)->toBe(Roles::USER);
            expect($roles[$bob->id])->toBeArray();
            expect($roles[$bob->id][0]->slug)->toBe(Roles::USER_CONFIRMED);
        });

        describe('Caching', function () {
            it('should use cache when possible', function () {
                $api = app(AuthPublicApi::class);
                
                $user = alice($this, roles: [Roles::USER_CONFIRMED]);

                // Get roles once, then patch user roles directly in db
                $api->getRolesByUserIds([$user->id]);
                $user->roles()->update(['name' => 'NewRole']);

                $roles2 = $api->getRolesByUserIds([$user->id]);
                expect($roles2[$user->id])->toBeArray();
                expect($roles2[$user->id][0]->slug)->toBe(Roles::USER_CONFIRMED);
            });

            it('should clear cache when user email gets verified', function () {
                $api = app(AuthPublicApi::class);
                
                $user = alice($this, roles: [], isVerified: false);

                // Get roles once, then patch user roles directly in db
                $api->getRolesByUserIds([$user->id]);

                $this->actingAs($user);
                $this->get(verificationUrlFor($user));
                
                $roles2 = $api->getRolesByUserIds([$user->id]);
                expect($roles2[$user->id])->toBeArray();
                expect($roles2[$user->id][0]->slug)->toBe(Roles::USER_CONFIRMED);
            });
        });
    });

    describe('getUserIdsByRoles', function () {
        it('should return user IDs for users with specified roles', function () {
            $alice = alice($this, roles: [Roles::USER]);
            $bob = bob($this, roles: [Roles::USER_CONFIRMED]);
            $adminUser = admin($this);
            
            $api = app(AuthPublicApi::class);
            
            // Get users with USER role
            $userIds = $api->getUserIdsByRoles([Roles::USER]);
            expect($userIds)->toContain($alice->id);
            expect($userIds)->not->toContain($bob->id);
            
            // Get users with USER_CONFIRMED role
            $confirmedIds = $api->getUserIdsByRoles([Roles::USER_CONFIRMED]);
            expect($confirmedIds)->toContain($bob->id);
            expect($confirmedIds)->not->toContain($alice->id);
            
            // Get users with admin role
            $adminIds = $api->getUserIdsByRoles([Roles::ADMIN]);
            expect($adminIds)->toContain($adminUser->id);
        });
        
        it('should return user IDs for multiple roles', function () {
            $alice = alice($this, roles: [Roles::USER]);
            $bob = bob($this, roles: [Roles::USER_CONFIRMED]);
            
            $api = app(AuthPublicApi::class);
            
            $userIds = $api->getUserIdsByRoles([Roles::USER, Roles::USER_CONFIRMED]);
            expect($userIds)->toContain($alice->id);
            expect($userIds)->toContain($bob->id);
        });
        
        it('should only return active users by default', function () {
            $alice = alice($this, roles: [Roles::USER]);
            $alice->deactivate();
            
            $api = app(AuthPublicApi::class);
            
            $userIds = $api->getUserIdsByRoles([Roles::USER]);
            expect($userIds)->not->toContain($alice->id);
        });
        
        it('should return inactive users when activeOnly is false', function () {
            $alice = alice($this, roles: [Roles::USER]);
            $alice->deactivate();
            
            $api = app(AuthPublicApi::class);
            
            $userIds = $api->getUserIdsByRoles([Roles::USER], activeOnly: false);
            expect($userIds)->toContain($alice->id);
        });
        
        it('should return empty array for empty role slugs', function () {
            $api = app(AuthPublicApi::class);
            
            $userIds = $api->getUserIdsByRoles([]);
            expect($userIds)->toBe([]);
        });
    });

    describe('getAllActiveUserIds', function () {
        it('should return all active user IDs', function () {
            $alice = alice($this);
            $bob = bob($this);
            $inactive = carol($this);
            $inactive->deactivate();
            
            $api = app(AuthPublicApi::class);
            
            $userIds = $api->getAllActiveUserIds();
            expect($userIds)->toContain($alice->id);
            expect($userIds)->toContain($bob->id);
            expect($userIds)->not->toContain($inactive->id);
        });
    });

});