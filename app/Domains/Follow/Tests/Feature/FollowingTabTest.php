<?php

use App\Domains\Follow\Public\Api\FollowPublicApi;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Following tab visibility — canViewFollowingTab', function () {
    it('is not visible to guests', function () {
        $profileUser = alice($this);

        $api = app(FollowPublicApi::class);
        expect($api->canViewFollowingTab($profileUser->id, null))->toBeFalse();
    });

    it('is visible to authenticated users by default', function () {
        $profileUser = alice($this);
        $viewer = bob($this);

        $api = app(FollowPublicApi::class);
        expect($api->canViewFollowingTab($profileUser->id, $viewer->id))->toBeTrue();
    });

    it('is hidden from others when the profile owner enables the privacy setting', function () {
        $profileUser = alice($this);
        $viewer = bob($this);

        app(SettingsPublicApi::class)->setValue($profileUser->id, 'profile', 'hide-following-tab', true);

        expect(app(FollowPublicApi::class)->canViewFollowingTab($profileUser->id, $viewer->id))->toBeFalse();
    });

    it('owner can always see their own following tab even when privacy is on', function () {
        $profileUser = alice($this);

        app(SettingsPublicApi::class)->setValue($profileUser->id, 'profile', 'hide-following-tab', true);

        expect(app(FollowPublicApi::class)->canViewFollowingTab($profileUser->id, $profileUser->id))->toBeTrue();
    });
});

describe('Following tab route protection', function () {
    it('redirects guests to login', function () {
        $owner = alice($this);
        $slug = profileSlugFromApi($owner->id);

        $this->get(route('profile.show.following', $slug))
            ->assertRedirect('/login');
    });

    it('returns 200 for an authenticated viewer when privacy is off', function () {
        $owner = alice($this);
        $viewer = bob($this);
        $slug = profileSlugFromApi($owner->id);

        $this->actingAs($viewer)
            ->get(route('profile.show.following', $slug))
            ->assertOk();
    });

    it('returns 403 for other users when owner has hidden the tab', function () {
        $owner = alice($this);
        $viewer = bob($this);
        app(SettingsPublicApi::class)->setValue($owner->id, 'profile', 'hide-following-tab', true);
        $slug = profileSlugFromApi($owner->id);

        $this->actingAs($viewer)
            ->get(route('profile.show.following', $slug))
            ->assertForbidden();
    });

    it('returns 200 for the owner even when they have hidden the tab', function () {
        $owner = alice($this);
        app(SettingsPublicApi::class)->setValue($owner->id, 'profile', 'hide-following-tab', true);
        $slug = profileSlugFromApi($owner->id);

        $this->actingAs($owner)
            ->get(route('profile.show.following', $slug))
            ->assertOk();
    });
});

describe('Following tab — visibility indicator', function () {
    it('shows the visibility_off icon when owner views their hidden tab', function () {
        $owner = alice($this);
        app(SettingsPublicApi::class)->setValue($owner->id, 'profile', 'hide-following-tab', true);
        $slug = profileSlugFromApi($owner->id);

        $this->actingAs($owner)
            ->get(route('profile.show.following', $slug))
            ->assertOk()
            ->assertSee('visibility_off');
    });

    it('shows the visibility icon when owner views their visible tab', function () {
        $owner = alice($this);
        $slug = profileSlugFromApi($owner->id);

        $html = $this->actingAs($owner)
            ->get(route('profile.show.following', $slug))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('visibility')
            ->and($html)->not->toContain('visibility_off');
    });

    it('does not show the visibility indicator to other users', function () {
        $owner = alice($this);
        $viewer = bob($this);
        $slug = profileSlugFromApi($owner->id);

        $this->actingAs($viewer)
            ->get(route('profile.show.following', $slug))
            ->assertOk()
            ->assertDontSee('data-follow-visibility-indicator', false);
    });
});

describe('getFollowerIds', function () {
    it('returns follower IDs for a user', function () {
        $author = alice($this);
        $follower1 = bob($this);
        $follower2 = registerUserThroughForm($this, ['name' => 'Charlie', 'email' => 'charlie@example.com']);
        followUser($follower1->id, $author->id);
        followUser($follower2->id, $author->id);

        $ids = app(FollowPublicApi::class)->getFollowerIds($author->id);

        expect($ids)->toContain($follower1->id)->and($ids)->toContain($follower2->id);
    });

    it('returns empty array when user has no followers', function () {
        $user = alice($this);
        expect(app(FollowPublicApi::class)->getFollowerIds($user->id))->toBeEmpty();
    });
});
