<?php

use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Auth\PublicApi\AuthPublicApi;
use App\Domains\Auth\PublicApi\Roles;
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

});