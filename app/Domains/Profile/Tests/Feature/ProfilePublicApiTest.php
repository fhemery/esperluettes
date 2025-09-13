<?php

declare(strict_types=1);

use App\Domains\Auth\Events\EmailVerified;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Profile\Models\Profile;
use App\Domains\Profile\PublicApi\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile public API', function () {
    it('should return a profile by id', function () {
        $api = app(ProfilePublicApi::class);
        
        $user = alice($this);
        $this->actingAs($user);

        $profile = $api->getPublicProfile($user->id);
        expect($profile->user_id)->toBe($user->id);
    });

    describe('Cache management', function() {
        it('should use cache when possible', function () {
            $api = app(ProfilePublicApi::class);
            
            $user = alice($this);
            $this->actingAs($user);

            // Get profile once, then patch user name directly in db
            $profile = $api->getPublicProfile($user->id);
            Profile::where('user_id', $user->id)->update(['display_name' => 'Bob']);

            $profile2 = $api->getPublicProfile($user->id);
            expect($profile2->user_id)->toBe($user->id);
            expect($profile2->display_name)->toBe('Alice');
        });

        it('should clear cache when receiving a VerifyEmail event', function () {
            $api = app(ProfilePublicApi::class);
            
            $user = alice($this);
            $this->actingAs($user);

            // Get profile once, then patch user name directly in db
            // Then dispatch event to flush cache
            $profile = $api->getPublicProfile($user->id);
            Profile::where('user_id', $user->id)->update(['display_name' => 'Bob']);
            dispatchEvent(new EmailVerified($user->id));

            $profile2 = $api->getPublicProfile($user->id);
            expect($profile2->user_id)->toBe($user->id);
            expect($profile2->display_name)->toBe('Bob');
        });
    });
});