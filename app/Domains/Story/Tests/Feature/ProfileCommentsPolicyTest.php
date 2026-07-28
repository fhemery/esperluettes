<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\ParameterType;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Services\ProfileCommentsPolicy;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Story owns the comments tab, so it owns the setting that gates it and the
 * rule that reads it. These used to live in Profile.
 */

function hideComments(int $userId, bool $hidden = true): void
{
    setSettingsValue(
        $userId,
        StoryServiceProvider::TAB_PROFILE,
        StoryServiceProvider::KEY_HIDE_COMMENTS_SECTION,
        $hidden
    );
}

function profileSlugOf(int $userId): string
{
    return app(ProfilePublicApi::class)->getPublicProfile($userId)->slug;
}

describe('hide-comments-section setting', function () {
    it('is registered by Story under the profile privacy section', function () {
        $params = app(SettingsPublicApi::class)->getParametersForSection(
            StoryServiceProvider::TAB_PROFILE,
            StoryServiceProvider::SECTION_PRIVACY
        );

        $param = collect($params)->firstWhere('key', StoryServiceProvider::KEY_HIDE_COMMENTS_SECTION);

        expect($param)->not->toBeNull()
            ->and($param->type)->toBe(ParameterType::BOOL)
            ->and($param->default)->toBe(false)
            ->and($param->nameKey)->toBe('story::profile.settings.hide-comments-section.name');
    });

    it('can be updated through the settings page', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->putJson(route('settings.update', [
                'tab' => StoryServiceProvider::TAB_PROFILE,
                'key' => StoryServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            ]), ['value' => true])
            ->assertOk()
            ->assertJson(['success' => true]);

        expect(app(SettingsPublicApi::class)->getValue(
            $user->id,
            StoryServiceProvider::TAB_PROFILE,
            StoryServiceProvider::KEY_HIDE_COMMENTS_SECTION
        ))->toBe(true);
    });
});

describe('ProfileCommentsPolicy::canViewComments', function () {
    it('allows a confirmed viewer when the setting is off', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        expect(app(ProfileCommentsPolicy::class)->canViewComments($owner->id, $viewer->id))->toBe(true);
    });

    it('hides from regular users when the setting is on', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);
        hideComments($owner->id);

        expect(app(ProfileCommentsPolicy::class)->canViewComments($owner->id, $viewer->id))->toBe(false);
    });

    it('always allows the owner', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        hideComments($owner->id);

        expect(app(ProfileCommentsPolicy::class)->canViewComments($owner->id, $owner->id))->toBe(true);
    });

    it('lets moderators, admins and tech admins through', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        hideComments($owner->id);
        $policy = app(ProfileCommentsPolicy::class);

        $moderator = bob($this, roles: [Roles::MODERATOR]);
        $admin = carol($this, roles: [Roles::ADMIN]);
        $techAdmin = daniel($this, roles: [Roles::TECH_ADMIN]);

        expect($policy->canViewComments($owner->id, $moderator->id))->toBe(true)
            ->and($policy->canViewComments($owner->id, $admin->id))->toBe(true)
            ->and($policy->canViewComments($owner->id, $techAdmin->id))->toBe(true);
    });

    it('refuses guests', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);

        expect(app(ProfileCommentsPolicy::class)->canViewComments($owner->id, null))->toBe(false);
    });

    it('refuses unconfirmed viewers even when the setting is off', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER]);

        expect(app(ProfileCommentsPolicy::class)->canViewComments($owner->id, $viewer->id))->toBe(false);
    });
});

describe('comments tab on the profile page', function () {
    it('is reachable when the setting is off', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        $this->actingAs($viewer)
            ->get(route('profile.show.tab', [profileSlugOf($owner->id), 'comments']))
            ->assertOk();
    });

    it('disappears for regular users when the setting is on', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);
        hideComments($owner->id);
        $slug = profileSlugOf($owner->id);

        $this->actingAs($viewer)
            ->get(route('profile.show.tab', [$slug, 'comments']))
            ->assertRedirect(route('profile.show', $slug));
    });

    it('stays reachable for the owner and for moderators', function () {
        $owner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $moderator = bob($this, roles: [Roles::USER_CONFIRMED, Roles::MODERATOR]);
        hideComments($owner->id);
        $slug = profileSlugOf($owner->id);

        $this->actingAs($owner)->get(route('profile.show.tab', [$slug, 'comments']))->assertOk();
        $this->actingAs($moderator)->get(route('profile.show.tab', [$slug, 'comments']))->assertOk();
    });
});
