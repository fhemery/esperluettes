<?php

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearSettingsRegistry();
});

describe('SettingsPublicApi - registerTab', function () {
    it('registers a tab successfully', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'story',
            order: 10,
            nameKey: 'story::settings.tab_name',
            icon: 'menu_book',
        ));

        $api = app(SettingsPublicApi::class);
        $tab = $api->getTab('story');

        expect($tab)->not->toBeNull();
        expect($tab->id)->toBe('story');
        expect($tab->order)->toBe(10);
        expect($tab->icon)->toBe('menu_book');
    });

    it('throws exception for duplicate tab ID', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'story',
            order: 10,
            nameKey: 'story::settings.tab_name',
        ));

        registerSettingsTab(new SettingsTabDefinition(
            id: 'story',
            order: 20,
            nameKey: 'story::settings.tab_name_2',
        ));
    })->throws(InvalidArgumentException::class, "Settings tab 'story' is already registered.");

    it('returns tabs sorted by order', function () {
        registerSettingsTab(new SettingsTabDefinition(id: 'third', order: 30, nameKey: 'third'));
        registerSettingsTab(new SettingsTabDefinition(id: 'first', order: 10, nameKey: 'first'));
        registerSettingsTab(new SettingsTabDefinition(id: 'second', order: 20, nameKey: 'second'));

        $api = app(SettingsPublicApi::class);
        $tabs = $api->getAllTabs();

        expect($tabs)->toHaveCount(3);
        expect($tabs[0]->id)->toBe('first');
        expect($tabs[1]->id)->toBe('second');
        expect($tabs[2]->id)->toBe('third');
    });

    it('handles case-insensitive tab ID lookups', function () {
        registerSettingsTab(new SettingsTabDefinition(
            id: 'Story',
            order: 10,
            nameKey: 'story::settings.tab_name',
        ));

        $api = app(SettingsPublicApi::class);

        expect($api->getTab('story'))->not->toBeNull();
        expect($api->getTab('STORY'))->not->toBeNull();
        expect($api->getTab('Story'))->not->toBeNull();
    });
});

describe('SettingsPublicApi - registerSection', function () {
    it('registers a section under an existing tab', function () {
        registerSettingsTab(new SettingsTabDefinition(id: 'story', order: 10, nameKey: 'story'));

        registerSettingsSection(new SettingsSectionDefinition(
            tabId: 'story',
            id: 'reading',
            order: 10,
            nameKey: 'story::settings.sections.reading.name',
            descriptionKey: 'story::settings.sections.reading.description',
        ));

        $api = app(SettingsPublicApi::class);
        $sections = $api->getSectionsForTab('story');

        expect($sections)->toHaveCount(1);
        expect($sections[0]->id)->toBe('reading');
    });

    it('throws exception when tab does not exist', function () {
        registerSettingsSection(new SettingsSectionDefinition(
            tabId: 'nonexistent',
            id: 'reading',
            order: 10,
            nameKey: 'test',
        ));
    })->throws(InvalidArgumentException::class, "Cannot register section 'reading': tab 'nonexistent' does not exist.");

    it('throws exception for duplicate section ID within tab', function () {
        registerSettingsTab(new SettingsTabDefinition(id: 'story', order: 10, nameKey: 'story'));

        registerSettingsSection(new SettingsSectionDefinition(
            tabId: 'story',
            id: 'reading',
            order: 10,
            nameKey: 'test',
        ));

        registerSettingsSection(new SettingsSectionDefinition(
            tabId: 'story',
            id: 'reading',
            order: 20,
            nameKey: 'test2',
        ));
    })->throws(InvalidArgumentException::class, "Settings section 'reading' is already registered under tab 'story'.");

    it('allows same section ID in different tabs', function () {
        registerSettingsTab(new SettingsTabDefinition(id: 'story', order: 10, nameKey: 'story'));
        registerSettingsTab(new SettingsTabDefinition(id: 'notification', order: 20, nameKey: 'notification'));

        registerSettingsSection(new SettingsSectionDefinition(tabId: 'story', id: 'general', order: 10, nameKey: 'test'));
        registerSettingsSection(new SettingsSectionDefinition(tabId: 'notification', id: 'general', order: 10, nameKey: 'test'));

        $api = app(SettingsPublicApi::class);

        expect($api->getSectionsForTab('story'))->toHaveCount(1);
        expect($api->getSectionsForTab('notification'))->toHaveCount(1);
    });

    it('returns sections sorted by order', function () {
        registerSettingsTab(new SettingsTabDefinition(id: 'story', order: 10, nameKey: 'story'));

        registerSettingsSection(new SettingsSectionDefinition(tabId: 'story', id: 'third', order: 30, nameKey: 'third'));
        registerSettingsSection(new SettingsSectionDefinition(tabId: 'story', id: 'first', order: 10, nameKey: 'first'));
        registerSettingsSection(new SettingsSectionDefinition(tabId: 'story', id: 'second', order: 20, nameKey: 'second'));

        $api = app(SettingsPublicApi::class);
        $sections = $api->getSectionsForTab('story');

        expect($sections[0]->id)->toBe('first');
        expect($sections[1]->id)->toBe('second');
        expect($sections[2]->id)->toBe('third');
    });
});

describe('SettingsPublicApi - registerParameter', function () {
    it('registers a parameter under an existing section', function () {
        registerTestSettingsStructure('story', 'reading');

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'story::settings.params.font_size.name',
        ));

        $api = app(SettingsPublicApi::class);
        $param = $api->getParameter('story', 'font_size');

        expect($param)->not->toBeNull();
        expect($param->key)->toBe('font_size');
        expect($param->default)->toBe(16);
    });

    it('throws exception when tab does not exist', function () {
        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'nonexistent',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));
    })->throws(InvalidArgumentException::class, "Cannot register parameter 'font_size': tab 'nonexistent' does not exist.");

    it('throws exception when section does not exist', function () {
        registerSettingsTab(new SettingsTabDefinition(id: 'story', order: 10, nameKey: 'story'));

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'nonexistent',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));
    })->throws(InvalidArgumentException::class, "Cannot register parameter 'font_size': section 'nonexistent' does not exist under tab 'story'.");

    it('throws exception for duplicate key within tab', function () {
        registerTestSettingsStructure('story', 'reading');

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 18,
            order: 20,
            nameKey: 'test2',
        ));
    })->throws(InvalidArgumentException::class, "Settings parameter 'font_size' is already registered under tab 'story'.");

    it('allows same key in different tabs', function () {
        registerTestSettingsStructure('story', 'reading');
        registerTestSettingsStructure('notification', 'general');

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'enabled',
            type: ParameterType::BOOL,
            default: true,
            order: 10,
            nameKey: 'test',
        ));

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'notification',
            sectionId: 'general',
            key: 'enabled',
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'test',
        ));

        $api = app(SettingsPublicApi::class);

        expect($api->getParameter('story', 'enabled')->default)->toBeTrue();
        expect($api->getParameter('notification', 'enabled')->default)->toBeFalse();
    });

    it('returns parameters for section sorted by order', function () {
        registerTestSettingsStructure('story', 'reading');

        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story', sectionId: 'reading', key: 'third',
            type: ParameterType::INT, default: 3, order: 30, nameKey: 'third',
        ));
        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story', sectionId: 'reading', key: 'first',
            type: ParameterType::INT, default: 1, order: 10, nameKey: 'first',
        ));
        registerSettingsParameter(new SettingsParameterDefinition(
            tabId: 'story', sectionId: 'reading', key: 'second',
            type: ParameterType::INT, default: 2, order: 20, nameKey: 'second',
        ));

        $api = app(SettingsPublicApi::class);
        $params = $api->getParametersForSection('story', 'reading');

        expect($params[0]->key)->toBe('first');
        expect($params[1]->key)->toBe('second');
        expect($params[2]->key)->toBe('third');
    });
});
