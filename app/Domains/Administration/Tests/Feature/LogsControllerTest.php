<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @property AdminNavigationRegistry $registry
 * @method \Illuminate\Testing\TestResponse get(string $uri, array $headers = [])
 * @method \Illuminate\Testing\TestResponse actingAs(\Illuminate\Contracts\Auth\Authenticatable $user)
 */
uses(TestCase::class, RefreshDatabase::class);

describe('LogsController', function () {
    $createTestLogFiles = function () {
        $logDir = storage_path('logs');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logDir . '/test1.log', "Test log line 1\nTest log line 2\nTest log line 3\n");
        file_put_contents($logDir . '/test2.log', "Another test log line 1\nAnother test log line 2\n");
    };

    $cleanupTestLogFiles = function () {
        $logDir = storage_path('logs');

        if (is_file($logDir . '/test1.log')) {
            unlink($logDir . '/test1.log');
        }

        if (is_file($logDir . '/test2.log')) {
            unlink($logDir . '/test2.log');
        }
    };

    beforeEach(function () use ($createTestLogFiles) {
        $this->registry = app(AdminNavigationRegistry::class);
        $this->registry->clear();

        // Register logs page in navigation for layout tests
        $this->registry->registerGroup('system', 'System', 10);
        $this->registry->registerPage(
            'logs',
            'system',
            'Logs',
            route('administration.logs'),
            'description',
            [Roles::TECH_ADMIN],
            20,
            'custom'
        );

        // Create test log files
        $createTestLogFiles();
    });

    afterEach(function () use ($cleanupTestLogFiles) {
        // Clean up test log files
        $cleanupTestLogFiles();
    });

    it('redirects unauthenticated users', function () {
        $response = $this->get(route('administration.logs'));

        $response->assertRedirect('/login');
    });

    it('denies access to regular users', function () {
        $user = alice($this, [], true, [Roles::USER]);

        $response = $this->actingAs($user)->get(route('administration.logs'));

        $response->assertStatus(302);
    });

    it('denies access to confirmed users', function () {
        $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

        $response = $this->actingAs($user)->get(route('administration.logs'));

        $response->assertStatus(302);
    });

    it('allows tech admin to access logs page', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs'));

        $response->assertStatus(200);
    });

    it('logs page lists available log files', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs'));

        $response->assertStatus(200);
        $response->assertSee('test1.log');
        $response->assertSee('test2.log');
    });

    it('logs page displays content of selected file', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs', ['file' => 'test1.log']));

        $response->assertStatus(200);
        $response->assertSee('Test log line 1');
        $response->assertSee('Test log line 2');
    });

    it('logs page handles non existent file', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs', ['file' => 'nonexistent.log']));

        $response->assertStatus(200);
        $response->assertDontSee('Test log line 1');
    });

    it('tech admin can download log file', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs.download', ['file' => 'test1.log']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=test1.log');
    });

    it('download returns 404 for non existent file', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs.download', ['file' => 'nonexistent.log']));

        $response->assertStatus(404);
    });

    it('download prevents directory traversal attack', function () {
        $user = alice($this, [], true, [Roles::TECH_ADMIN]);

        $response = $this->actingAs($user)->get(route('administration.logs.download', ['file' => '../../../etc/passwd']));

        $response->assertStatus(404);
    });
});
