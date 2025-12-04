<?php

use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use App\Domains\Config\Public\Contracts\ConfigParameterVisibility;
use App\Domains\Config\Public\Events\ConfigParameterUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearParameterDefinitions();
});

describe('ConfigParameterController - index', function () {
    it('requires authentication', function () {
        $response = $this->get(route('config.admin.parameters.index'));

        $response->assertRedirect(route('login'));
    });

    it('requires admin or tech-admin role', function () {
        $this->actingAs(alice($this));

        $response = $this->get(route('config.admin.parameters.index'));

        // Role middleware redirects unauthorized users
        $response->assertRedirect();
    });

    it('allows admin to view the page', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->get(route('config.admin.parameters.index'));

        $response->assertOk();
        $response->assertViewIs('config::pages.admin.parameters.index');
    });

    it('allows tech-admin to view the page', function () {
        $this->actingAs(techAdmin($this));

        $response = $this->get(route('config.admin.parameters.index'));

        $response->assertOk();
    });

    it('groups parameters by domain', function () {
        $this->actingAs(techAdmin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'story',
            key: 'max_chapters',
            type: ParameterType::INT,
            default: 50,
        ));

        registerParameter(new ConfigParameterDefinition(
            domain: 'calendar',
            key: 'event_duration',
            type: ParameterType::INT,
            default: 30,
        ));

        $response = $this->get(route('config.admin.parameters.index'));

        $response->assertOk();
        $response->assertViewHas('grouped');
        
        $grouped = $response->viewData('grouped');
        expect($grouped->has('story'))->toBeTrue();
        expect($grouped->has('calendar'))->toBeTrue();
    });

    it('filters parameters based on admin visibility for regular admin', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'tech_only',
            type: ParameterType::BOOL,
            default: false,
            visibility: ConfigParameterVisibility::TECH_ADMINS_ONLY,
        ));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'all_admins',
            type: ParameterType::BOOL,
            default: true,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->get(route('config.admin.parameters.index'));

        $grouped = $response->viewData('grouped');
        $testParams = $grouped->get('test');
        
        expect(count($testParams))->toBe(1);
        expect($testParams[0]['definition']->key)->toBe('all_admins');
    });

    it('shows all parameters for tech-admin', function () {
        $this->actingAs(techAdmin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'tech_only',
            type: ParameterType::BOOL,
            default: false,
            visibility: ConfigParameterVisibility::TECH_ADMINS_ONLY,
        ));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'all_admins',
            type: ParameterType::BOOL,
            default: true,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->get(route('config.admin.parameters.index'));

        $grouped = $response->viewData('grouped');
        $testParams = $grouped->get('test');
        
        expect(count($testParams))->toBe(2);
    });
});

describe('ConfigParameterController - update', function () {
    it('requires authentication', function () {
        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 200,
        ]);

        $response->assertUnauthorized();
    });

    it('requires admin or tech-admin role', function () {
        $this->actingAs(alice($this));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 200,
        ]);

        // Role middleware redirects unauthorized users (JSON returns 302 as redirect)
        $response->assertRedirect();
    });

    it('returns 404 for unregistered parameter', function () {
        $this->actingAs(admin($this));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'nonexistent']), [
            'value' => 200,
        ]);

        $response->assertNotFound();
    });

    it('updates an INT parameter', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            constraints: ['min' => 1, 'max' => 1000],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 500,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        
        expect(getParameterValue('max_items', 'test'))->toBe(500);
    });

    it('updates a BOOL parameter', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'feature_enabled',
            type: ParameterType::BOOL,
            default: false,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'feature_enabled']), [
            'value' => true,
        ]);

        $response->assertOk();
        expect(getParameterValue('feature_enabled', 'test'))->toBeTrue();
    });

    it('updates a STRING parameter', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'site_name',
            type: ParameterType::STRING,
            default: 'Default',
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'site_name']), [
            'value' => 'Custom Name',
        ]);

        $response->assertOk();
        expect(getParameterValue('site_name', 'test'))->toBe('Custom Name');
    });

    it('validates INT constraints - min', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            constraints: ['min' => 10, 'max' => 1000],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 5,
        ]);

        $response->assertUnprocessable();
    });

    it('validates INT constraints - max', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            constraints: ['min' => 10, 'max' => 1000],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 2000,
        ]);

        $response->assertUnprocessable();
    });

    it('emits ConfigParameterUpdated event', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 500,
        ]);

        $event = latestEventOf(ConfigParameterUpdated::name(), ConfigParameterUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->parameter->domain)->toBe('test');
        expect($event->parameter->key)->toBe('max_items');
        expect($event->parameter->value)->toBe(500);
    });

    it('denies regular admin from updating tech-admin-only parameter', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'tech_setting',
            type: ParameterType::INT,
            default: 100,
            visibility: ConfigParameterVisibility::TECH_ADMINS_ONLY,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'tech_setting']), [
            'value' => 500,
        ]);

        // Service throws AuthorizationException, controller returns 403
        $response->assertForbidden();
    });

    it('allows tech-admin to update tech-admin-only parameter', function () {
        $this->actingAs(techAdmin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'tech_setting',
            type: ParameterType::INT,
            default: 100,
            visibility: ConfigParameterVisibility::TECH_ADMINS_ONLY,
        ));

        $response = $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'tech_setting']), [
            'value' => 500,
        ]);

        $response->assertOk();
        expect(getParameterValue('tech_setting', 'test'))->toBe(500);
    });
});

describe('ConfigParameterController - reset', function () {
    it('requires authentication', function () {
        $response = $this->deleteJson(route('config.admin.parameters.reset', ['domain' => 'test', 'key' => 'max_items']));

        $response->assertUnauthorized();
    });

    it('requires admin or tech-admin role', function () {
        $this->actingAs(alice($this));

        $response = $this->deleteJson(route('config.admin.parameters.reset', ['domain' => 'test', 'key' => 'max_items']));

        // Role middleware redirects unauthorized users
        $response->assertRedirect();
    });

    it('returns 404 for unregistered parameter', function () {
        $this->actingAs(admin($this));

        $response = $this->deleteJson(route('config.admin.parameters.reset', ['domain' => 'test', 'key' => 'nonexistent']));

        $response->assertNotFound();
    });

    it('resets parameter to default value', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        // First set a custom value
        $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 500,
        ]);
        expect(getParameterValue('max_items', 'test'))->toBe(500);

        // Then reset
        $response = $this->deleteJson(route('config.admin.parameters.reset', ['domain' => 'test', 'key' => 'max_items']));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'defaultValue' => 100,
        ]);
        expect(getParameterValue('max_items', 'test'))->toBe(100);
    });

    it('emits ConfigParameterUpdated event on reset', function () {
        $this->actingAs(admin($this));

        registerParameter(new ConfigParameterDefinition(
            domain: 'test',
            key: 'max_items',
            type: ParameterType::INT,
            default: 100,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        // Set then reset
        $this->putJson(route('config.admin.parameters.update', ['domain' => 'test', 'key' => 'max_items']), [
            'value' => 500,
        ]);

        $this->deleteJson(route('config.admin.parameters.reset', ['domain' => 'test', 'key' => 'max_items']));

        $event = latestEventOf(ConfigParameterUpdated::name(), ConfigParameterUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->parameter->value)->toBe(100); // Reset to default
        expect($event->parameter->previousValue)->toBe(500);
    });
});
