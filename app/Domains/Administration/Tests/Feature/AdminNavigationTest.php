<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @method \Illuminate\Testing\TestResponse get(string $uri, array $headers = [])
 * @method \Illuminate\Testing\TestResponse actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
 */
uses(TestCase::class, RefreshDatabase::class);

/**
 * Count Filament sidebar items in the /admin layout.
 *
 * We rely on the CSS class used by Filament's sidebar buttons.
 */
function countFilamentSidebarLinks(string $html): int
{
    return substr_count($html, 'fi-sidebar-item-button');
}

/**
 * Count new admin sidebar links rendered via the custom sidebar component.
 *
 * We use the dedicated data-test-id attribute on each sidebar link.
 */
function countNewAdminSidebarLinks(string $html): int
{
    return substr_count($html, 'data-test-id="admin-sidebar-link"');
}

describe('Admin navigation', function () {

    it('has same number of sidebar links between Filament and new admin for admin role', function () {
        $user = admin($this, [], true, [Roles::ADMIN, Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        // Old Filament admin
        $filamentResponse = $this->get('/admin');
        $filamentResponse->assertOk();
        $filamentCount = countFilamentSidebarLinks($filamentResponse->getContent());

        // New custom admin
        $newAdminResponse = $this->get('/administration');
        $newAdminResponse->assertOk();
        $newAdminCount = countNewAdminSidebarLinks($newAdminResponse->getContent());

        expect($newAdminCount)->toBe($filamentCount);
    });

    it('renders hardcoded dashboard and back-to-site links in the admin sidebar', function () {
        // Use a tech admin user who has full access to the admin area
        $user = techAdmin($this, [], true, [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $response = $this->get('/administration');
        $response->assertOk();

        $html = $response->getContent();

        // Dashboard link
        $dashboardUrl = route('administration.dashboard');
        expect($html)->toContain('href="' . $dashboardUrl . '"')
            ->and($html)->toContain(__('administration::dashboard.title'));

        // Back to site link
        $backToSiteUrl = route('dashboard');
        expect($html)->toContain('href="' . $backToSiteUrl . '"')
            ->and($html)->toContain(__('administration::navigation.back-to-site'));
    });

    it('has same number of sidebar links between Filament and new admin for tech admin role', function () {
        $user = techAdmin($this, [], true, [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $filamentResponse = $this->get('/admin');
        $filamentResponse->assertOk();
        $filamentCount = countFilamentSidebarLinks($filamentResponse->getContent());

        $newAdminResponse = $this->get('/administration');
        $newAdminResponse->assertOk();
        $newAdminCount = countNewAdminSidebarLinks($newAdminResponse->getContent());

        expect($newAdminCount)->toBe($filamentCount);
    });

    it('has same number of sidebar links between Filament and new admin for moderator role', function () {
        $user = moderator($this, [], true, [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $filamentResponse = $this->get('/admin');
        $filamentResponse->assertOk();
        $filamentCount = countFilamentSidebarLinks($filamentResponse->getContent());

        $newAdminResponse = $this->get('/administration');
        $newAdminResponse->assertOk();
        $newAdminCount = countNewAdminSidebarLinks($newAdminResponse->getContent());

        expect($newAdminCount)->toBe($filamentCount);
    });

    describe('Registration management and sorting', function () {
        beforeEach(function () {
            $this->registry = app(AdminNavigationRegistry::class);
            $this->registry->clear();

            // Create and authenticate a tech admin user for layout rendering
            $this->adminUser = alice($this, [], true, [Roles::TECH_ADMIN]);
            $this->actingAs($this->adminUser);
        });

        it('sorts groups by sort_order correctly', function () {
            // Register groups in random order with specific sort orders
            $this->registry->registerGroup('system', 'System', 30);
            $this->registry->registerGroup('content', 'Content', 10);
            $this->registry->registerGroup('moderation', 'Moderation', 20);
            $this->registry->registerGroup('analytics', 'Analytics', 40);

            // Add one page to each group so they appear in navigation
            $this->registry->registerPage('dashboard', 'system', 'Dashboard', AdminRegistryTarget::url('http://localhost/admin/dashboard'), 'dashboard', [], 10, 'custom');
            $this->registry->registerPage('stories', 'content', 'Stories', AdminRegistryTarget::url('http://localhost/admin/stories'), 'book', [], 10, 'custom');
            $this->registry->registerPage('reports', 'moderation', 'Reports', AdminRegistryTarget::url('http://localhost/admin/reports'), 'report', [], 10, 'custom');
            $this->registry->registerPage('stats', 'analytics', 'Statistics', AdminRegistryTarget::url('http://localhost/admin/stats'), 'analytics', [], 10, 'custom');

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
            $this->registry->registerPage('users', 'system', 'Users', AdminRegistryTarget::url('http://localhost/admin/users'), 'people', [], 40, 'custom');
            $this->registry->registerPage('dashboard', 'system', 'Dashboard', AdminRegistryTarget::url('http://localhost/admin/dashboard'), 'dashboard', [], 10, 'custom');
            $this->registry->registerPage('settings', 'system', 'Settings', AdminRegistryTarget::url('http://localhost/admin/settings'), 'settings', [], 30, 'custom');
            $this->registry->registerPage('logs', 'system', 'Logs', AdminRegistryTarget::url('http://localhost/admin/logs'), 'description', [], 20, 'custom');

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
            $this->registry->registerPage('chapters', 'content', 'Chapters', AdminRegistryTarget::url('http://localhost/admin/chapters'), 'book', [], 30, 'custom');
            $this->registry->registerPage('stories', 'content', 'Stories', AdminRegistryTarget::url('http://localhost/admin/stories'), 'book', [], 10, 'custom');
            $this->registry->registerPage('tags', 'content', 'Tags', AdminRegistryTarget::url('http://localhost/admin/tags'), 'label', [], 20, 'custom');

            // Add pages to System group out of order
            $this->registry->registerPage('users', 'system', 'Users', AdminRegistryTarget::url('http://localhost/admin/users'), 'people', [], 30, 'custom');
            $this->registry->registerPage('dashboard', 'system', 'Dashboard', AdminRegistryTarget::url('http://localhost/admin/dashboard'), 'dashboard', [], 10, 'custom');
            $this->registry->registerPage('settings', 'system', 'Settings', AdminRegistryTarget::url('http://localhost/admin/settings'), 'settings', [], 20, 'custom');

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
            $this->registry->registerPage('page1', 'first', 'Page 1', AdminRegistryTarget::url('http://localhost/admin/page1'), 'page', []);
            $this->registry->registerPage('page2', 'first', 'Page 2', AdminRegistryTarget::url('http://localhost/admin/page2'), 'page', []);
            $this->registry->registerPage('page3', 'second', 'Page 3', AdminRegistryTarget::url('http://localhost/admin/page3'), 'page', []);

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
});
