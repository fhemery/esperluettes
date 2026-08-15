<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\ParameterType;
use App\Domains\Story\Private\Services\StoryPreferenceService;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * StoryPreferenceService is a container singleton and memoizes per
 * "{userId}:{key}" for the whole request. The container is NOT rebuilt between
 * two HTTP calls inside one test, so a test that flips a preference *between*
 * two requests must call
 * `app()->forgetInstance(StoryPreferenceService::class)` after setSettingsValue,
 * or split into two `it()` blocks. Setting the preference before the first
 * request — the normal case — needs nothing.
 */

describe('Story preferences settings registration', function () {
    it('registers the stories settings tab', function () {
        $tab = app(SettingsPublicApi::class)->getTab(StoryServiceProvider::TAB_STORIES);

        expect($tab)->not->toBeNull();
        expect($tab->id)->toBe('stories');
        expect($tab->nameKey)->toBe('story::settings.tabs.stories');
    });

    it('registers the reading section', function () {
        $sections = app(SettingsPublicApi::class)
            ->getSectionsForTab(StoryServiceProvider::TAB_STORIES);

        expect($sections)->toHaveCount(1);
        expect($sections[0]->id)->toBe('reading');
        expect($sections[0]->nameKey)->toBe('story::settings.sections.reading.name');
    });

    it('registers both preferences as booleans defaulting to false', function () {
        $params = app(SettingsPublicApi::class)->getParametersForSection(
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::SECTION_READING,
        );

        foreach ([
            StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
            StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
        ] as $key) {
            $param = collect($params)->firstWhere('key', $key);

            expect($param)->not->toBeNull();
            expect($param->type)->toBe(ParameterType::BOOL);
            expect($param->default)->toBe(false);
        }
    });

    it('leaves the hide-comments-section registration untouched', function () {
        $param = app(SettingsPublicApi::class)->getParameter(
            StoryServiceProvider::TAB_PROFILE,
            StoryServiceProvider::KEY_HIDE_COMMENTS_SECTION,
        );

        expect($param)->not->toBeNull();
        expect($param->type)->toBe(ParameterType::BOOL);
    });
});

describe('Story preferences settings page', function () {
    it('shows the Histoires tab on the settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('story::settings.tabs.stories'));
    });

    it('lets a non-confirmed user toggle each preference', function () {
        $user = alice($this, roles: [Roles::USER]);

        foreach ([
            StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
            StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
        ] as $key) {
            $response = $this->actingAs($user)->putJson(route('settings.update', [
                'tab' => StoryServiceProvider::TAB_STORIES,
                'key' => $key,
            ]), ['value' => true]);

            $response->assertOk();
            $response->assertJson(['success' => true]);

            expect(getSettingsValue($user->id, StoryServiceProvider::TAB_STORIES, $key))->toBe(true);
        }
    });

    it('lets a confirmed user toggle each preference', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        foreach ([
            StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
            StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
        ] as $key) {
            $response = $this->actingAs($user)->putJson(route('settings.update', [
                'tab' => StoryServiceProvider::TAB_STORIES,
                'key' => $key,
            ]), ['value' => true]);

            $response->assertOk();

            expect(getSettingsValue($user->id, StoryServiceProvider::TAB_STORIES, $key))->toBe(true);
        }
    });
});

describe('StoryPreferenceService', function () {
    it('returns false for a user who never toggled anything', function () {
        $user = alice($this);
        $this->actingAs($user);

        $service = app(StoryPreferenceService::class);

        expect($service->hidesTriggerWarnings())->toBeFalse();
        expect($service->hidesStoriesWithTriggerWarnings())->toBeFalse();
    });

    it('returns false for a guest', function () {
        $service = app(StoryPreferenceService::class);

        expect($service->hidesTriggerWarnings())->toBeFalse();
        expect($service->hidesStoriesWithTriggerWarnings())->toBeFalse();
    });

    it('returns the stored value for each preference', function () {
        $user = alice($this);

        setSettingsValue(
            $user->id,
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::KEY_HIDE_TRIGGER_WARNINGS,
            true,
        );
        setSettingsValue(
            $user->id,
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
            true,
        );

        $this->actingAs($user);
        $service = app(StoryPreferenceService::class);

        expect($service->hidesTriggerWarnings())->toBeTrue();
        expect($service->hidesStoriesWithTriggerWarnings())->toBeTrue();
        // Explicit user id resolves the same values without an authenticated user.
        expect($service->hidesTriggerWarnings($user->id))->toBeTrue();
        expect($service->hidesStoriesWithTriggerWarnings($user->id))->toBeTrue();
    });

    it('resolves the preference service as a singleton', function () {
        expect(app(StoryPreferenceService::class))->toBe(app(StoryPreferenceService::class));
    });
});
