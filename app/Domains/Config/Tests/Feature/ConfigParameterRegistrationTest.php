<?php

use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Config\Public\Contracts\ConfigParameterType;
use App\Domains\Config\Public\Contracts\ConfigParameterVisibility;
use App\Domains\Config\Public\Services\ConfigParameterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearParameterDefinitions();
});

describe('ConfigPublicApi - registerParameter', function () {
    it('registers an INT parameter with default value', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 100,
        ));

        $value = getParameterValue('max_items', 'test');
        expect($value)->toBe(100);
    });

    it('registers a STRING parameter with default value', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'site_name',
            type: ConfigParameterType::STRING,
            default: 'My Site',
        ));

        $value = getParameterValue('site_name', 'test');
        expect($value)->toBe('My Site');
    });

    it('registers a BOOL parameter with default value', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'feature_enabled',
            type: ConfigParameterType::BOOL,
            default: true,
        ));

        $value = getParameterValue('feature_enabled', 'test');
        expect($value)->toBeTrue();
    });

    it('registers multiple parameters from different domains', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'story',
            key: 'max_chapters',
            type: ConfigParameterType::INT,
            default: 50,
        ));

        registerParameter(new ConfigParameterDefinition(
            domain: 'calendar',
            key: 'event_duration',
            type: ConfigParameterType::INT,
            default: 30,
        ));

        expect(getParameterValue('max_chapters', 'story'))->toBe(50);
        expect(getParameterValue('event_duration', 'calendar'))->toBe(30);
    });

    it('allows same key in different domains', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'story',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 100,
        ));

        registerParameter(new ConfigParameterDefinition(
            domain: 'calendar',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 200,
        ));

        expect(getParameterValue('max_items', 'story'))->toBe(100);
        expect(getParameterValue('max_items', 'calendar'))->toBe(200);
    });
});

describe('ConfigPublicApi - getParameterValue', function () {
    it('returns null for unregistered parameters', function () {
        $value = getParameterValue('nonexistent', 'test');
        expect($value)->toBeNull();
    });

    it('returns null for unregistered domain', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 100,
        ));

        $value = getParameterValue('max_items', 'other_domain');
        expect($value)->toBeNull();
    });

    it('handles case-insensitive domain lookups', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'Test',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 100,
        ));

        expect(getParameterValue('max_items', 'test'))->toBe(100);
        expect(getParameterValue('max_items', 'TEST'))->toBe(100);
        expect(getParameterValue('max_items', 'TeSt'))->toBe(100);
    });

    it('handles case-insensitive key lookups', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'MaxItems',
            type: ConfigParameterType::INT,
            default: 100,
        ));

        expect(getParameterValue('maxitems', 'test'))->toBe(100);
        expect(getParameterValue('MAXITEMS', 'test'))->toBe(100);
        expect(getParameterValue('MaxItems', 'test'))->toBe(100);
    });

    it('returns overridden value when set via service', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 100,
            constraints: ['min' => 1, 'max' => 1000],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        // Set override via service (as admin)
        $this->actingAs(admin($this));
        $service = app(ConfigParameterService::class);
        $service->setParameterValue('max_items', 'test', 500);

        $value = getParameterValue('max_items', 'test');
        expect($value)->toBe(500);
    });

    it('returns default value after reset', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ConfigParameterType::INT,
            default: 100,
            constraints: ['min' => 1, 'max' => 1000],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $this->actingAs(admin($this));
        $service = app(ConfigParameterService::class);
        
        // Set then reset
        $service->setParameterValue('max_items', 'test', 500);
        expect(getParameterValue('max_items', 'test'))->toBe(500);
        
        $service->resetParameterToDefault('max_items', 'test');
        expect(getParameterValue('max_items', 'test'))->toBe(100);
    });

    it('correctly casts INT values from DB', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'count',
            type: ConfigParameterType::INT,
            default: 10,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $this->actingAs(admin($this));
        $service = app(ConfigParameterService::class);
        $service->setParameterValue('count', 'test', 42);

        $value = getParameterValue('count', 'test');
        expect($value)->toBe(42);
        expect($value)->toBeInt();
    });

    it('correctly casts BOOL values from DB', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'enabled',
            type: ConfigParameterType::BOOL,
            default: false,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $this->actingAs(admin($this));
        $service = app(ConfigParameterService::class);
        $service->setParameterValue('enabled', 'test', true);

        $value = getParameterValue('enabled', 'test');
        expect($value)->toBeTrue();
        expect($value)->toBeBool();
    });

    it('correctly casts STRING values from DB', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'title',
            type: ConfigParameterType::STRING,
            default: 'Default',
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $this->actingAs(admin($this));
        $service = app(ConfigParameterService::class);
        $service->setParameterValue('title', 'test', 'Custom Title');

        $value = getParameterValue('title', 'test');
        expect($value)->toBe('Custom Title');
        expect($value)->toBeString();
    });

    it('correctly handles TIME type (stores seconds)', function () {
        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'duration',
            type: ConfigParameterType::TIME,
            default: 3600, // 1 hour
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        // Default value
        expect(getParameterValue('duration', 'test'))->toBe(3600);

        // Update to 2 days
        $this->actingAs(admin($this));
        $service = app(ConfigParameterService::class);
        $service->setParameterValue('duration', 'test', 172800); // 2 days in seconds

        $value = getParameterValue('duration', 'test');
        expect($value)->toBe(172800);
        expect($value)->toBeInt();
    });
});
