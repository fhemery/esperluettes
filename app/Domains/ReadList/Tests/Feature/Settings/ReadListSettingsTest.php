<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\ReadList\Public\Providers\ReadListServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ReadList settings registration', function () {
    it('registers readlist tab', function () {
        $settingsApi = app(SettingsPublicApi::class);

        $tab = $settingsApi->getTab(ReadListServiceProvider::TAB_READLIST);

        expect($tab)->not->toBeNull();
        expect($tab->id)->toBe('readlist');
        expect($tab->nameKey)->toBe('readlist::settings.tabs.readlist');
    });

    it('registers general section', function () {
        $settingsApi = app(SettingsPublicApi::class);

        $sections = $settingsApi->getSectionsForTab(ReadListServiceProvider::TAB_READLIST);

        expect($sections)->toHaveCount(1);
        expect($sections[0]->id)->toBe('general');
        expect($sections[0]->nameKey)->toBe('readlist::settings.sections.general.name');
    });

    it('registers hide-up-to-date parameter with correct options', function () {
        $settingsApi = app(SettingsPublicApi::class);

        $params = $settingsApi->getParametersForSection(
            ReadListServiceProvider::TAB_READLIST,
            ReadListServiceProvider::SECTION_GENERAL
        );

        $param = collect($params)->firstWhere('key', ReadListServiceProvider::KEY_HIDE_UP_TO_DATE);

        expect($param)->not->toBeNull();
        expect($param->type)->toBe(ParameterType::BOOL);
        expect($param->default)->toBe(false);
    });
});

describe('ReadList settings page integration', function () {
    it('shows readlist tab on settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('readlist::settings.tabs.readlist'));
    });

    it('can update hide-up-to-date preference via settings page', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', [
                'tab' => ReadListServiceProvider::TAB_READLIST,
                'key' => ReadListServiceProvider::KEY_HIDE_UP_TO_DATE,
            ]), [
                'value' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $settingsApi = app(SettingsPublicApi::class);
        $value = $settingsApi->getValue(
            $user->id,
            ReadListServiceProvider::TAB_READLIST,
            ReadListServiceProvider::KEY_HIDE_UP_TO_DATE
        );
        expect($value)->toBe(true);
    });
});

describe('ReadList index page uses settings default', function () {
    it('shows all stories by default when setting is false', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story and add to readlist
        setUserCredits($author->id, 10);
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        createPublishedChapter($this, $story, $author);

        // Add to reader's readlist
        $this->actingAs($reader);
        app(\App\Domains\ReadList\Private\Services\ReadListService::class)->addStory($reader->id, $story->id);

        // Mark as read (up to date)
        markAsRead($this, $story->chapters->first());

        // Default setting is false (show all), so story should appear
        $response = $this->actingAs($reader)->get(route('readlist.index'));
        $response->assertOk();

        $vm = $response->viewData('vm');
        expect($vm->stories->count())->toBe(1);
    });

    it('hides up-to-date stories when setting is true', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story and add to readlist
        setUserCredits($author->id, 10);
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        createPublishedChapter($this, $story, $author);

        // Add to reader's readlist
        $this->actingAs($reader);
        app(\App\Domains\ReadList\Private\Services\ReadListService::class)->addStory($reader->id, $story->id);

        // Mark as read (up to date)
        markAsRead($this, $story->chapters->first());

        // Set user preference to hide up-to-date stories
        setSettingsValue(
            $reader->id,
            ReadListServiceProvider::TAB_READLIST,
            ReadListServiceProvider::KEY_HIDE_UP_TO_DATE,
            true
        );

        // Story should be hidden because it's up to date
        $response = $this->actingAs($reader)->get(route('readlist.index'));
        $response->assertOk();

        $vm = $response->viewData('vm');
        expect($vm->stories->count())->toBe(0);
    });

    it('respects explicit request parameter over setting', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story and add to readlist
        setUserCredits($author->id, 10);
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        createPublishedChapter($this, $story, $author);

        // Add to reader's readlist
        $this->actingAs($reader);
        app(\App\Domains\ReadList\Private\Services\ReadListService::class)->addStory($reader->id, $story->id);

        // Mark as read (up to date)
        markAsRead($this, $story->chapters->first());

        // Set user preference to hide up-to-date stories
        setSettingsValue(
            $reader->id,
            ReadListServiceProvider::TAB_READLIST,
            ReadListServiceProvider::KEY_HIDE_UP_TO_DATE,
            true
        );

        // But explicitly request to show all (hide_up_to_date=0)
        $response = $this->actingAs($reader)->get(route('readlist.index', ['hide_up_to_date' => '0']));
        $response->assertOk();

        $vm = $response->viewData('vm');
        // Story should appear because we explicitly requested to show all
        expect($vm->stories->count())->toBe(1);
    });

    it('checkbox reflects current filter state from settings', function () {
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $reader = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story and add to readlist
        setUserCredits($author->id, 10);
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        createPublishedChapter($this, $story, $author);

        // Add to reader's readlist
        $this->actingAs($reader);
        app(\App\Domains\ReadList\Private\Services\ReadListService::class)->addStory($reader->id, $story->id);

        // Set user preference to hide up-to-date stories
        setSettingsValue(
            $reader->id,
            ReadListServiceProvider::TAB_READLIST,
            ReadListServiceProvider::KEY_HIDE_UP_TO_DATE,
            true
        );

        $response = $this->actingAs($reader)->get(route('readlist.index'));
        $response->assertOk();

        // The checkbox should be checked because hideUpToDate is true
        $hideUpToDate = $response->viewData('hideUpToDate');
        expect($hideUpToDate)->toBe(true);
    });
});
