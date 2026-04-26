<?php

use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Support\Facades\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearSettingsRegistry();
    View::addNamespace('test-settings-stubs', __DIR__ . '/../stubs');
});

afterEach(function () {
    clearSettingsRegistry();
});

describe('Settings custom-view tab — visibility', function () {
    it('appears in the navigation even without registered parameters', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 10,
            nameKey: 'test::tab.name',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertDontSee(__('settings::settings.no_settings'));
    });

    it('appears alongside standard tabs that have parameters', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 5,
            nameKey: 'test::tab.custom',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test::font_size',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertDontSee(__('settings::settings.no_settings'));
    });
});

describe('Settings custom-view tab — AJAX endpoint', function () {
    it('returns the custom view content instead of the standard tab partial', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 10,
            nameKey: 'test::tab.name',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.tab', ['tab' => 'custom']));

        $response->assertOk();
        $response->assertSee('Custom tab view rendered');
        $response->assertDontSee(__('settings::settings.no_settings_in_tab'));
    });

    it('returns 404 for a non-existent custom-view tab', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.tab', ['tab' => 'nonexistent']));

        $response->assertNotFound();
    });

    it('requires authentication for the AJAX endpoint', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 10,
            nameKey: 'test::tab.name',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        $response = $this->get(route('settings.tab', ['tab' => 'custom']));

        $response->assertRedirect(route('login'));
    });
});

describe('Settings custom-view tab — initial page load', function () {
    it('renders the custom view on initial load when the tab is active', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 10,
            nameKey: 'test::tab.name',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index', ['tab' => 'custom']));

        $response->assertOk();
        $response->assertSee('Custom tab view rendered');
        $response->assertDontSee(__('settings::settings.no_settings_in_tab'));
    });

    it('renders the custom view when it is the first (default) tab', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 1,
            nameKey: 'test::tab.name',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('Custom tab view rendered');
    });
});

describe('Settings custom-view tab — standard tabs unaffected', function () {
    it('continues to render standard tab content normally', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 5,
            nameKey: 'test::tab.custom',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test::font_size',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.tab', ['tab' => 'story']));

        $response->assertOk();
        $response->assertDontSee('Custom tab view rendered');
    });

    it('does not expose custom view content for standard tabs', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test::font_size',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.tab', ['tab' => 'story']));

        $response->assertOk();
        $response->assertDontSee('Custom tab view rendered');
    });

    it('standard tab update and reset endpoints are unaffected', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'custom',
            order: 5,
            nameKey: 'test::tab.custom',
            customViewPath: 'test-settings-stubs::custom-view',
        ));

        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test::font_size',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', ['tab' => 'story', 'key' => 'font_size']), [
                'value' => 20,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        expect(getSettingsValue($user->id, 'story', 'font_size'))->toBe(20);
    });
});
