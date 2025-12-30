<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Services\ProfilePrivacyService;
use App\Domains\Profile\Public\Providers\ProfileServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile settings registration', function () {
    it('registers profile tab', function () {
        $settingsApi = app(SettingsPublicApi::class);

        $tab = $settingsApi->getTab(ProfileServiceProvider::TAB_PROFILE);

        expect($tab)->not->toBeNull();
        expect($tab->id)->toBe('profile');
        expect($tab->nameKey)->toBe('profile::settings.tabs.profile');
    });

    it('registers privacy section', function () {
        $settingsApi = app(SettingsPublicApi::class);

        $sections = $settingsApi->getSectionsForTab(ProfileServiceProvider::TAB_PROFILE);

        expect($sections)->toHaveCount(1);
        expect($sections[0]->id)->toBe('privacy');
        expect($sections[0]->nameKey)->toBe('profile::settings.sections.privacy.name');
    });

    it('registers hide-comments-section parameter with correct type', function () {
        $settingsApi = app(SettingsPublicApi::class);

        $params = $settingsApi->getParametersForSection(
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::SECTION_PRIVACY
        );

        $param = collect($params)->firstWhere('key', ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION);

        expect($param)->not->toBeNull();
        expect($param->type)->toBe(ParameterType::BOOL);
        expect($param->default)->toBe(false);
    });
});

describe('Profile settings page integration', function () {
    it('shows profile tab on settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('profile::settings.tabs.profile'));
    });

    it('can update hide-comments-section preference via settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', [
                'tab' => ProfileServiceProvider::TAB_PROFILE,
                'key' => ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            ]), [
                'value' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $settingsApi = app(SettingsPublicApi::class);
        $value = $settingsApi->getValue(
            $user->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION
        );
        expect($value)->toBe(true);
    });
});

describe('ProfilePrivacyService - canViewComments', function () {
    it('allows viewing when setting is not enabled', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Default setting is false (not hidden)
        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, $viewer->id))->toBe(true);
    });

    it('hides comments from regular users when setting is enabled', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, $viewer->id))->toBe(false);
    });

    it('allows profile owner to see their own comments when hidden', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, $profileOwner->id))->toBe(true);
    });

    it('allows moderators to see hidden comments', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $moderator = bob($this, roles: [Roles::MODERATOR]);

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, $moderator->id))->toBe(true);
    });

    it('allows admins to see hidden comments', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $admin = bob($this, roles: [Roles::ADMIN]);

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, $admin->id))->toBe(true);
    });

    it('allows tech admins to see hidden comments', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $techAdmin = bob($this, roles: [Roles::TECH_ADMIN]);

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, $techAdmin->id))->toBe(true);
    });

    it('hides comments from guests when setting is enabled', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $privacyService = app(ProfilePrivacyService::class);

        expect($privacyService->canViewComments($profileOwner->id, null))->toBe(false);
    });
});

describe('Profile comments tab visibility', function () {
    it('shows comments component when setting is not enabled', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Get profile slug
        $profile = \App\Domains\Profile\Private\Models\Profile::where('user_id', $profileOwner->id)->first();

        $response = $this->actingAs($viewer)->get(route('profile.show.comments', $profile->slug));

        $response->assertOk();
        // Should not see the hidden message
        $response->assertDontSee(__('profile::settings.privacy.comments-hidden'));
    });

    it('shows hidden message when setting is enabled for regular users', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Get profile slug
        $profile = \App\Domains\Profile\Private\Models\Profile::where('user_id', $profileOwner->id)->first();

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $response = $this->actingAs($viewer)->get(route('profile.show.comments', $profile->slug));

        $response->assertOk();
        $response->assertSee(__('profile::settings.privacy.comments-hidden'));
    });

    it('shows comments to profile owner even when hidden', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Get profile slug
        $profile = \App\Domains\Profile\Private\Models\Profile::where('user_id', $profileOwner->id)->first();

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $response = $this->actingAs($profileOwner)->get(route('profile.show.comments', $profile->slug));

        $response->assertOk();
        $response->assertDontSee(__('profile::settings.privacy.comments-hidden'));
    });

    it('shows comments to moderators even when hidden', function () {
        $profileOwner = alice($this, roles: [Roles::USER_CONFIRMED]);
        $moderator = bob($this, roles: [Roles::USER_CONFIRMED, Roles::MODERATOR]);

        // Get profile slug
        $profile = \App\Domains\Profile\Private\Models\Profile::where('user_id', $profileOwner->id)->first();

        // Enable hide comments setting
        setSettingsValue(
            $profileOwner->id,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION,
            true
        );

        $response = $this->actingAs($moderator)->get(route('profile.show.comments', $profile->slug));

        $response->assertOk();
        $response->assertDontSee(__('profile::settings.privacy.comments-hidden'));
    });
});
