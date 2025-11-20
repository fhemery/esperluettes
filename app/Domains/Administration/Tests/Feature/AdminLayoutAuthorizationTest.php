<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin layout authorization', function () {
    beforeEach(function () {
        $this->registry = app(AdminNavigationRegistry::class);

    });

    it('throws error for unauthenticated users', function () {
        expect(fn() => Blade::render('<x-admin::layout></x-admin::layout>'))
            ->toThrow('Authentication required');
    });

    it('throws error for regular users', function () {
        $user = alice($this, [], true, [Roles::USER]);
        $this->actingAs($user);

        expect(fn() => Blade::render('<x-admin::layout></x-admin::layout>'))
            ->toThrow('Insufficient permissions');
    });

    it('throws error for confirmed users without admin roles', function () {
        $user = alice($this, [], true, [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        expect(fn() => Blade::render('<x-admin::layout></x-admin::layout>'))
            ->toThrow('Insufficient permissions');
    });

    it('renders layout for moderators', function () {
        $user = moderator($this);
        $this->actingAs($user);

        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Verify layout components are present
        expect($render)->toContain('header-logo')
            ->and($render)->toContain('administration::navigation.back-to-site');
    });

    it('renders layout for admins', function () {
        $user = admin($this);
        $this->actingAs($user);

        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Verify layout components are present
        expect($render)->toContain('header-logo')
            ->and($render)->toContain('administration::navigation.back-to-site');
    });

    it('renders layout for tech admins', function () {
        $user = techAdmin($this);
        $this->actingAs($user);

        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Verify layout components are present
        expect($render)->toContain('header-logo')
            ->and($render)->toContain('administration::navigation.back-to-site');
    });

    it('shows correct navigation for moderators', function () {
        $user = moderator($this);

        // Add moderator-specific navigation
        $this->registry->registerGroup(
            'fake-group',
            'Fake Group',
            20,
        );
        $this->registry->registerPage(
            'reports',
            'fake-group',
            'Reports',
            AdminRegistryTarget::url('http://localhost/admin/reports'),
            'report',
            [Roles::MODERATOR],
            20,
            'custom'
        );

        $response = $this->actingAs($user)->get(route('administration.dashboard'));
        $content = $response->getContent();

        expect($content)->toContain('Reports')
            ->and($content)->toContain('href="http://localhost/admin/reports"');
    });

    it('shows admin-only navigation for admins', function () {
        $user = admin($this);

        // Add admin-only navigation
        $this->registry->registerGroup(
            'fake-group',
            'Fake Group',
            20,
        );
        $this->registry->registerPage(
            'system-settings',
            'fake-group',
            'System Settings',
            AdminRegistryTarget::url('http://localhost/admin/system'),
            'settings',
            [Roles::ADMIN],
            30,
            'custom'
        );

        $response = $this->actingAs($user)->get(route('administration.dashboard'));
        $content = $response->getContent();

        expect($content)->toContain('System Settings')
            ->and($content)->toContain('Fake Group')
            ->and($content)->toContain('href="http://localhost/admin/system"');
    });

    it('shows tech admin navigation for tech admins', function () {
        $user = techAdmin($this);

        // Add tech admin-only navigation
        $this->registry->registerGroup(
            'fake-group',
            'Fake Group',
            20,
        );
        $this->registry->registerPage(
            'debug-tools',
            'fake-group',
            'Debug Tools',
            AdminRegistryTarget::url('http://localhost/admin/debug'),
            'bug_report',
            [Roles::TECH_ADMIN],
            40,
            'custom'
        );

        $response = $this->actingAs($user)->get(route('administration.dashboard'));
        $content = $response->getContent();

        expect($content)->toContain('Debug Tools')
            ->and($content)->toContain('Fake Group')
            ->and($content)->toContain('href="http://localhost/admin/debug"');
    });

    it('hides restricted navigation from lower-level users', function () {
        $moderator = moderator($this);

        // Add admin-only navigation
        $this->registry->registerGroup(
            'fake-group',
            'Fake Group',
            20,
        );
        $this->registry->registerPage(
            'admin-only',
            'fake-group',
            'Admin Only',
            AdminRegistryTarget::url('http://localhost/admin/admin-only'),
            'admin_panel_settings',
            [Roles::ADMIN],
            50,
            'custom'
        );

        $response = $this->actingAs($moderator)->get(route('administration.dashboard'));
        $content = $response->getContent();

        // Should not show admin-only pages to moderators
        expect($content)->not->toContain('Admin Only')
            ->and($content)->not->toContain('href="http://localhost/admin/admin-only"');
    });

    it('handles users with multiple roles correctly', function () {
        $user = admin($this, [], true, [Roles::ADMIN, Roles::MODERATOR, Roles::USER_CONFIRMED]);

        // Add pages for different roles
        $this->registry->registerGroup(
            'fake-group',
            'Fake Group',
            20,
        );
        $this->registry->registerPage(
            'moderator-page',
            'fake-group',
            'Moderator Page',
            AdminRegistryTarget::url('http://localhost/admin/moderator'),
            'gavel',
            [Roles::MODERATOR],
            20,
            'custom'
        );

        $this->registry->registerPage(
            'admin-page',
            'fake-group',
            'Admin Page',
            AdminRegistryTarget::url('http://localhost/admin/admin'),
            'admin_panel_settings',
            [Roles::ADMIN],
            30,
            'custom'
        );

        $response = $this->actingAs($user)->get(route('administration.dashboard'));
        $content = $response->getContent();

        expect($content)->toContain('Moderator Page')
            ->and($content)->toContain('Admin Page')
            ->and($content)->toContain('href="http://localhost/admin/moderator"')
            ->and($content)->toContain('href="http://localhost/admin/admin"');
    });
});
