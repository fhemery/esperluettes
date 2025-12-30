<?php

use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearSettingsRegistry();
});
afterEach(function () {
    clearSettingsRegistry();
});

describe('SettingsPublicApi - getValue', function () {
    it('returns default value when no override exists', function () {
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
        $value = getSettingsValue($user->id, 'story', 'font_size');

        expect($value)->toBe(16);
    });

    it('returns null for unregistered parameter', function () {
        $user = alice($this);
        $value = getSettingsValue($user->id, 'nonexistent', 'font_size');

        expect($value)->toBeNull();
    });

    it('returns overridden value when set', function () {
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

        $value = getSettingsValue($user->id, 'story', 'font_size');
        expect($value)->toBe(20);
    });

    it('returns different values for different users', function () {
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

        expect(getSettingsValue($user1->id, 'story', 'font_size'))->toBe(18);
        expect(getSettingsValue($user2->id, 'story', 'font_size'))->toBe(24);
    });

    it('handles case-insensitive lookups', function () {
        registerTestSettingsStructure('Story', 'Reading', new SettingsParameterDefinition(
            tabId: 'Story',
            sectionId: 'Reading',
            key: 'FontSize',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'story', 'fontsize', 20);

        expect(getSettingsValue($user->id, 'STORY', 'FONTSIZE'))->toBe(20);
        expect(getSettingsValue($user->id, 'story', 'fontsize'))->toBe(20);
    });
});

describe('SettingsPublicApi - setValue', function () {
    it('stores value in database', function () {
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

        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'domain' => 'story',
            'key' => 'font_size',
            'value' => '20',
        ]);
    });

    it('removes override when value equals default', function () {
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

        // Set non-default value
        setSettingsValue($user->id, 'story', 'font_size', 20);
        $this->assertDatabaseHas('settings', ['user_id' => $user->id, 'key' => 'font_size']);

        // Set back to default
        setSettingsValue($user->id, 'story', 'font_size', 16);
        $this->assertDatabaseMissing('settings', ['user_id' => $user->id, 'key' => 'font_size']);
    });

    it('validates INT constraints', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test',
            constraints: ['min' => 12, 'max' => 28],
        ));

        $user = alice($this);

        setSettingsValue($user->id, 'story', 'font_size', 100);
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('validates ENUM constraints', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'theme',
            type: ParameterType::ENUM,
            default: 'light',
            order: 10,
            nameKey: 'test',
            constraints: ['options' => ['light' => 'Light', 'dark' => 'Dark']],
        ));

        $user = alice($this);

        setSettingsValue($user->id, 'story', 'theme', 'invalid');
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('accepts valid ENUM value', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'theme',
            type: ParameterType::ENUM,
            default: 'light',
            order: 10,
            nameKey: 'test',
            constraints: ['options' => ['light' => 'Light', 'dark' => 'Dark']],
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'story', 'theme', 'dark');

        expect(getSettingsValue($user->id, 'story', 'theme'))->toBe('dark');
    });
});

describe('SettingsPublicApi - resetToDefault', function () {
    it('removes override from database', function () {
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
        $this->assertDatabaseHas('settings', ['user_id' => $user->id, 'key' => 'font_size']);

        resetSettingsToDefault($user->id, 'story', 'font_size');
        $this->assertDatabaseMissing('settings', ['user_id' => $user->id, 'key' => 'font_size']);
    });

    it('returns default value after reset', function () {
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
        expect(getSettingsValue($user->id, 'story', 'font_size'))->toBe(20);

        resetSettingsToDefault($user->id, 'story', 'font_size');
        expect(getSettingsValue($user->id, 'story', 'font_size'))->toBe(16);
    });
});

describe('SettingsPublicApi - type casting', function () {
    it('correctly casts INT values', function () {
        registerTestSettingsStructure('test', 'general', new SettingsParameterDefinition(
            tabId: 'test',
            sectionId: 'general',
            key: 'count',
            type: ParameterType::INT,
            default: 10,
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'test', 'count', 42);

        $value = getSettingsValue($user->id, 'test', 'count');
        expect($value)->toBe(42);
        expect($value)->toBeInt();
    });

    it('correctly casts BOOL values', function () {
        registerTestSettingsStructure('test', 'general', new SettingsParameterDefinition(
            tabId: 'test',
            sectionId: 'general',
            key: 'enabled',
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'test', 'enabled', true);

        $value = getSettingsValue($user->id, 'test', 'enabled');
        expect($value)->toBeTrue();
        expect($value)->toBeBool();
    });

    it('correctly casts STRING values', function () {
        registerTestSettingsStructure('test', 'general', new SettingsParameterDefinition(
            tabId: 'test',
            sectionId: 'general',
            key: 'title',
            type: ParameterType::STRING,
            default: 'Default',
            order: 10,
            nameKey: 'test',
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'test', 'title', 'Custom');

        $value = getSettingsValue($user->id, 'test', 'title');
        expect($value)->toBe('Custom');
        expect($value)->toBeString();
    });

    it('correctly casts RANGE values', function () {
        registerTestSettingsStructure('test', 'general', new SettingsParameterDefinition(
            tabId: 'test',
            sectionId: 'general',
            key: 'volume',
            type: ParameterType::RANGE,
            default: 50,
            order: 10,
            nameKey: 'test',
            constraints: ['min' => 0, 'max' => 100, 'step' => 10],
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'test', 'volume', 80);

        $value = getSettingsValue($user->id, 'test', 'volume');
        expect($value)->toBe(80);
        expect($value)->toBeInt();
    });

    it('correctly casts MULTI_SELECT values', function () {
        registerTestSettingsStructure('test', 'general', new SettingsParameterDefinition(
            tabId: 'test',
            sectionId: 'general',
            key: 'channels',
            type: ParameterType::MULTI_SELECT,
            default: [],
            order: 10,
            nameKey: 'test',
            constraints: ['options' => ['email' => 'Email', 'push' => 'Push', 'sms' => 'SMS']],
        ));

        $user = alice($this);
        setSettingsValue($user->id, 'test', 'channels', ['email', 'push']);

        $value = getSettingsValue($user->id, 'test', 'channels');
        expect($value)->toBe(['email', 'push']);
        expect($value)->toBeArray();
    });
});
