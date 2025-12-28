<?php

use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearSettingsRegistry();
});

describe('Settings caching', function () {
    it('caches user settings after first access', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'story', 'font_size', 20);

        // Clear cache to simulate fresh state
        clearSettingsCache($user->id);

        // First access should populate cache
        $value1 = getSettingsValue($user->id, 'story', 'font_size');
        expect($value1)->toBe(20);

        // Verify cache exists
        expect(Cache::has("user_settings:{$user->id}"))->toBeTrue();
    });

    it('invalidates cache on setValue', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);

        // Set initial value (populates cache)
        setSettingsValue($user->id, 'story', 'font_size', 20);
        getSettingsValue($user->id, 'story', 'font_size');

        // Update value (should invalidate cache)
        setSettingsValue($user->id, 'story', 'font_size', 24);

        // Get new value (should reflect update)
        $value = getSettingsValue($user->id, 'story', 'font_size');
        expect($value)->toBe(24);
    });

    it('invalidates cache on resetToDefault', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);

        // Set value and access to populate cache
        setSettingsValue($user->id, 'story', 'font_size', 20);
        getSettingsValue($user->id, 'story', 'font_size');

        // Reset to default (should invalidate cache)
        resetSettingsToDefault($user->id, 'story', 'font_size');

        // Get value (should return default)
        $value = getSettingsValue($user->id, 'story', 'font_size');
        expect($value)->toBe(16);
    });

    it('maintains separate caches per user', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));

        $user1 = alice($this);
        $user2 = bob($this);

        setSettingsValue($user1->id, 'story', 'font_size', 18);
        setSettingsValue($user2->id, 'story', 'font_size', 24);

        // Populate caches
        getSettingsValue($user1->id, 'story', 'font_size');
        getSettingsValue($user2->id, 'story', 'font_size');

        // Update user1's setting
        setSettingsValue($user1->id, 'story', 'font_size', 20);

        // User2's cache should still be valid
        expect(getSettingsValue($user1->id, 'story', 'font_size'))->toBe(20);
        expect(getSettingsValue($user2->id, 'story', 'font_size'))->toBe(24);
    });
});
