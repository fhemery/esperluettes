<?php

use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    clearSettingsRegistry();
});

describe('Settings page - index', function () {
    it('requires authentication', function () {
        $response = $this->get(route('settings.index'));

        $response->assertRedirect(route('login'));
    });

    it('shows empty state when no settings registered', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('settings::settings.no_settings'));
    });

    it('shows tabs when settings are registered', function () {
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

    it('shows first tab content by default', function () {
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
    });

    it('can switch tabs via query parameter', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test::font_size',
        ));

        registerTestSettingsStructure('notification', 'general', new SettingsParameterDefinition(
            tabId: 'notification',
            sectionId: 'general',
            key: 'email_enabled',
            type: ParameterType::BOOL,
            default: true,
            order: 10,
            nameKey: 'test::email_enabled',
        ));

        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.index', ['tab' => 'notification']));

        $response->assertOk();
    });
});

describe('Settings page - tab partial', function () {
    it('returns tab content as partial', function () {
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
    });

    it('returns 404 for non-existent tab', function () {
        $user = alice($this);

        $response = $this->actingAs($user)->get(route('settings.tab', ['tab' => 'nonexistent']));

        $response->assertNotFound();
    });
});

describe('Settings page - update', function () {
    it('updates a setting value', function () {
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

    it('returns 404 for non-existent parameter', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', ['tab' => 'story', 'key' => 'nonexistent']), [
                'value' => 20,
            ]);

        $response->assertNotFound();
    });

    it('validates value against constraints', function () {
        registerTestSettingsStructure('story', 'reading', new SettingsParameterDefinition(
            tabId: 'story',
            sectionId: 'reading',
            key: 'font_size',
            type: ParameterType::INT,
            default: 16,
            order: 10,
            nameKey: 'test::font_size',
            constraints: ['min' => 12, 'max' => 28],
        ));

        $user = alice($this);

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', ['tab' => 'story', 'key' => 'font_size']), [
                'value' => 100,
            ]);

        $response->assertStatus(422);
    });
});

describe('Settings page - reset', function () {
    it('resets a setting to default', function () {
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

        // First set a value
        setSettingsValue($user->id, 'story', 'font_size', 20);
        expect(getSettingsValue($user->id, 'story', 'font_size'))->toBe(20);

        // Then reset
        $response = $this->actingAs($user)
            ->deleteJson(route('settings.reset', ['tab' => 'story', 'key' => 'font_size']));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        expect(getSettingsValue($user->id, 'story', 'font_size'))->toBe(16);
    });

    it('returns 404 for non-existent parameter', function () {
        $user = alice($this);

        $response = $this->actingAs($user)
            ->deleteJson(route('settings.reset', ['tab' => 'story', 'key' => 'nonexistent']));

        $response->assertNotFound();
    });
});

describe('Settings page - role-based visibility', function () {
    it('hides tabs with no visible parameters for user role', function () {
        registerTestSettingsStructure('admin', 'config', new SettingsParameterDefinition(
            tabId: 'admin',
            sectionId: 'config',
            key: 'admin_setting',
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'test::admin_setting',
            roles: ['admin', 'tech-admin'],
        ));

        $user = alice($this); // Regular user, not admin

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('settings::settings.no_settings'));
    });

    it('shows tabs with visible parameters for admin role', function () {
        registerTestSettingsStructure('admin', 'config', new SettingsParameterDefinition(
            tabId: 'admin',
            sectionId: 'config',
            key: 'admin_setting',
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'test::admin_setting',
            roles: ['admin', 'tech-admin'],
        ));

        $user = admin($this);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertDontSee(__('settings::settings.no_settings'));
    });

    it('denies update for parameter user cannot access', function () {
        registerTestSettingsStructure('admin', 'config', new SettingsParameterDefinition(
            tabId: 'admin',
            sectionId: 'config',
            key: 'admin_setting',
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'test::admin_setting',
            roles: ['admin', 'tech-admin'],
        ));

        $user = alice($this); // Regular user

        $response = $this->actingAs($user)
            ->putJson(route('settings.update', ['tab' => 'admin', 'key' => 'admin_setting']), [
                'value' => true,
            ]);

        $response->assertForbidden();
    });
});
