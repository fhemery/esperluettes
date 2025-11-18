<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AdminNavigationRegistry sorting', function () {
    beforeEach(function () {
        $this->registry = app(AdminNavigationRegistry::class);
        $this->registry->clear();
    });

    it('sorts groups by sort_order correctly', function () {
        // Register groups in random order with specific sort orders
        $this->registry->registerGroup('system', 'System', 30);
        $this->registry->registerGroup('content', 'Content', 10);
        $this->registry->registerGroup('moderation', 'Moderation', 20);
        $this->registry->registerGroup('analytics', 'Analytics', 40);

        // Add one page to each group so they appear in navigation
        $this->registry->registerPage('dashboard', 'system', 'Dashboard', 'http://localhost/admin/dashboard', 'dashboard', [], 10, 'custom');
        $this->registry->registerPage('stories', 'content', 'Stories', 'http://localhost/admin/stories', 'book', [], 10, 'custom');
        $this->registry->registerPage('reports', 'moderation', 'Reports', 'http://localhost/admin/reports', 'report', [], 10, 'custom');
        $this->registry->registerPage('stats', 'analytics', 'Statistics', 'http://localhost/admin/stats', 'analytics', [], 10, 'custom');

        // Render the admin layout to get actual HTML
        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Groups should appear in order: Content (10), Moderation (20), System (30), Analytics (40)
        $contentPos = strpos($render, 'Content');
        $moderationPos = strpos($render, 'Moderation');
        $systemPos = strpos($render, 'System');
        $analyticsPos = strpos($render, 'Analytics');

        expect($contentPos)->toBeLessThan($moderationPos, 'Content should appear before Moderation')
            ->and($moderationPos)->toBeLessThan($systemPos, 'Moderation should appear before System')
            ->and($systemPos)->toBeLessThan($analyticsPos, 'System should appear before Analytics');
    });

    it('sorts items within groups by sort_order correctly', function () {
        // Register a single group
        $this->registry->registerGroup('system', 'System', 10);

        // Register pages out of order with different sort orders
        $this->registry->registerPage('users', 'system', 'Users', 'http://localhost/admin/users', 'people', [], 40, 'custom');
        $this->registry->registerPage('dashboard', 'system', 'Dashboard', 'http://localhost/admin/dashboard', 'dashboard', [], 10, 'custom');
        $this->registry->registerPage('settings', 'system', 'Settings', 'http://localhost/admin/settings', 'settings', [], 30, 'custom');
        $this->registry->registerPage('logs', 'system', 'Logs', 'http://localhost/admin/logs', 'description', [], 20, 'custom');

        // Render the admin layout to get actual HTML
        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Find the positions of each page by their href attributes
        $dashboardPos = strpos($render, 'href="http://localhost/admin/dashboard"');
        $logsPos = strpos($render, 'href="http://localhost/admin/logs"');
        $settingsPos = strpos($render, 'href="http://localhost/admin/settings"');
        $usersPos = strpos($render, 'href="http://localhost/admin/users"');

        // Pages should appear in order: Dashboard (10), Logs (20), Settings (30), Users (40)
        expect($dashboardPos)->toBeLessThan($logsPos, 'Dashboard should appear before Logs')
            ->and($logsPos)->toBeLessThan($settingsPos, 'Logs should appear before Settings')
            ->and($settingsPos)->toBeLessThan($usersPos, 'Settings should appear before Users');
    });

    it('sorts items across multiple groups correctly', function () {
        // Register two groups
        $this->registry->registerGroup('content', 'Content', 10);
        $this->registry->registerGroup('system', 'System', 20);

        // Add pages to Content group out of order
        $this->registry->registerPage('chapters', 'content', 'Chapters', 'http://localhost/admin/chapters', 'book', [], 30, 'custom');
        $this->registry->registerPage('stories', 'content', 'Stories', 'http://localhost/admin/stories', 'book', [], 10, 'custom');
        $this->registry->registerPage('tags', 'content', 'Tags', 'http://localhost/admin/tags', 'label', [], 20, 'custom');

        // Add pages to System group out of order
        $this->registry->registerPage('users', 'system', 'Users', 'http://localhost/admin/users', 'people', [], 30, 'custom');
        $this->registry->registerPage('dashboard', 'system', 'Dashboard', 'http://localhost/admin/dashboard', 'dashboard', [], 10, 'custom');
        $this->registry->registerPage('settings', 'system', 'Settings', 'http://localhost/admin/settings', 'settings', [], 20, 'custom');

        // Render the admin layout to get actual HTML
        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // First check group order: Content should appear before System
        $contentGroupPos = strpos($render, 'Content');
        $systemGroupPos = strpos($render, 'System');
        expect($contentGroupPos)->toBeLessThan($systemGroupPos, 'Content group should appear before System group');

        // Check Content group items order: Stories (10), Tags (20), Chapters (30)
        $storiesPos = strpos($render, 'href="http://localhost/admin/stories"');
        $tagsPos = strpos($render, 'href="http://localhost/admin/tags"');
        $chaptersPos = strpos($render, 'href="http://localhost/admin/chapters"');

        expect($storiesPos)->toBeLessThan($tagsPos, 'Stories should appear before Tags in Content group')
            ->and($tagsPos)->toBeLessThan($chaptersPos, 'Tags should appear before Chapters in Content group');

        // Check System group items order: Dashboard (10), Settings (20), Users (30)
        $dashboardPos = strpos($render, 'href="http://localhost/admin/dashboard"');
        $settingsPos = strpos($render, 'href="http://localhost/admin/settings"');
        $usersPos = strpos($render, 'href="http://localhost/admin/users"');

        expect($dashboardPos)->toBeLessThan($settingsPos, 'Dashboard should appear before Settings in System group')
            ->and($settingsPos)->toBeLessThan($usersPos, 'Settings should appear before Users in System group');
    });

    it('handles default sort_order when not specified', function () {
        // Register groups without explicit sort_order (should default to 100)
        $this->registry->registerGroup('first', 'First Group');
        $this->registry->registerGroup('second', 'Second Group');

        // Register pages without explicit sort_order (should default to 100)
        $this->registry->registerPage('page1', 'first', 'Page 1', 'http://localhost/admin/page1', 'page', []);
        $this->registry->registerPage('page2', 'first', 'Page 2', 'http://localhost/admin/page2', 'page', []);
        $this->registry->registerPage('page3', 'second', 'Page 3', 'http://localhost/admin/page3', 'page', []);

        // Get navigation structure directly from registry
        $navigation = $this->registry->getNavigation();

        // Both groups should exist
        expect($navigation)->toHaveKey('first')
            ->and($navigation)->toHaveKey('second');

        // All groups should have sort_order of 100 (default)
        expect($navigation['first']['sort_order'])->toBe(100)
            ->and($navigation['second']['sort_order'])->toBe(100);

        // All pages should have sort_order of 100 (default)
        expect($navigation['first']['pages'][0]['sort_order'])->toBe(100)
            ->and($navigation['first']['pages'][1]['sort_order'])->toBe(100)
            ->and($navigation['second']['pages'][0]['sort_order'])->toBe(100);
    });
});
