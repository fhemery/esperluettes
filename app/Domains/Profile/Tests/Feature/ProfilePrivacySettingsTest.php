<?php

declare(strict_types=1);

use App\Domains\Profile\Public\Providers\ProfileServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
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

});

describe('Profile settings page integration', function () {
    it('shows profile tab on settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('profile::settings.tabs.profile'));
    });

});
