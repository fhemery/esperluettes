<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\EmailVerified;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Private\Api\ProfileApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile public API', function () {
    it('should return a profile by id', function () {
        $api = app(ProfileApi::class);
        
        $user = alice($this);
        $this->actingAs($user);

        $profile = $api->getPublicProfile($user->id);
        expect($profile->user_id)->toBe($user->id);
    });

    it('should return a full profile with expected fields', function () {
        $api = app(ProfileApi::class);

        $user = alice($this);
        $this->actingAs($user);

        $full = $api->getFullProfile($user->id);

        expect($full)->not()->toBeNull();
        expect($full->userId)->toBe($user->id);
        expect($full->displayName)->toBeString();
        expect($full->slug)->toBeString();
        expect($full->avatarUrl)->toBeString();
        expect($full->joinDateIso)->toBeString()->and($full->joinDateIso)->not()->toBe('');
        expect($full->roles)->toBeArray();

        $userConfirmedRole = array_filter($full->roles, fn ($role) => $role->slug === Roles::USER_CONFIRMED);
        expect($userConfirmedRole)->not()->toBeEmpty();
    });

    describe('Cache management', function() {
        it('should use cache when possible', function () {
            $api = app(ProfileApi::class);
            
            $user = alice($this);
            $this->actingAs($user);

            // Get profile once, then patch user name directly in db
            $api->getPublicProfile($user->id);
            Profile::where('user_id', $user->id)->update(['display_name' => 'Bob']);

            $profile2 = $api->getPublicProfile($user->id);
            expect($profile2->user_id)->toBe($user->id);
            expect($profile2->display_name)->toBe('Alice');
        });

        it('should clear cache when receiving a VerifyEmail event', function () {
            $api = app(ProfileApi::class);
            
            $user = alice($this);
            $this->actingAs($user);

            // Get profile once, then patch user name directly in db
            // Then dispatch event to flush cache
            $api->getPublicProfile($user->id);
            Profile::where('user_id', $user->id)->update(['display_name' => 'Bob']);
            dispatchEvent(new EmailVerified($user->id));

            $profile2 = $api->getPublicProfile($user->id);
            expect($profile2->user_id)->toBe($user->id);
            expect($profile2->display_name)->toBe('Bob');
        });
    });
});