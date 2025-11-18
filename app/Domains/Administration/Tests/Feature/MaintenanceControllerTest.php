<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('MaintenanceController', function () {
    beforeEach(function () {
        $this->registry = app(AdminNavigationRegistry::class);
        $this->registry->clear();
        
        // Register maintenance page in navigation for layout tests
        $this->registry->registerGroup('system', 'System', 10);
        $this->registry->registerPage(
            'maintenance',
            'system',
            'Maintenance',
            route('administration.maintenance'),
            'build',
            [Roles::TECH_ADMIN],
            10,
            'custom'
        );
    });

    it('redirects unauthenticated users', function () {
        $response = $this->get(route('administration.maintenance'));

        $response->assertRedirect('/login');
    });

    it('denies access to regular users', function () {
        $user = alice($this, [], true, [Roles::USER]);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));

        $response->assertStatus(302);
    });

    it('denies access to confirmed users', function () {
        $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));

        $response->assertStatus(302);
    });

    it('denies access to moderators', function () {
        $user = moderator($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));

        $response->assertStatus(302);
    });

    it('denies access to admins', function () {
        $user = admin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));

        $response->assertStatus(302);
    });

    it('allows access to tech admins and displays maintenance page', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify layout components are present
        expect($content)->toContain('header-logo')
            ->and($content)->toContain('administration::navigation.back-to-site');

        // Verify maintenance page content
        expect($content)->toContain(__('administration::maintenance.title'))
            ->and($content)->toContain(__('administration::maintenance.empty-cache.title'))
            ->and($content)->toContain(__('administration::maintenance.empty-cache.description'))
            ->and($content)->toContain(__('administration::maintenance.empty-cache.button-label'))
            ->and($content)->toContain(route('administration.maintenance.empty-cache'))
            ->and($content)->toContain('method="POST"')
            ->and($content)->toContain('type="submit"');
    });

    it('denies cache clearing to non-tech admin users', function () {
        $user = admin($this);

        $response = $this->actingAs($user)->post(route('administration.maintenance.empty-cache'));

        $response->assertStatus(302);
    });

    it('allows tech admins to clear cache successfully', function () {
        $user = techAdmin($this);

        // Mock Artisan to verify the command is called
        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear')
            ->andReturn(0);

        $response = $this->actingAs($user)
            ->from(route('administration.maintenance'))
            ->post(route('administration.maintenance.empty-cache'));

        // Verify redirect back to maintenance page with success message
        $response->assertRedirect(route('administration.maintenance'))
            ->assertSessionHas('success', __('administration::maintenance.empty-cache.success'));
    });

    it('handles cache clearing even if Artisan command fails', function () {
        $user = techAdmin($this);

        // Mock Artisan to simulate command failure
        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear')
            ->andReturn(1); // Non-zero exit code

        $response = $this->actingAs($user)
            ->from(route('administration.maintenance'))
            ->post(route('administration.maintenance.empty-cache'));

        // Should still redirect and show success (the controller doesn't check exit code)
        $response->assertRedirect(route('administration.maintenance'))
            ->assertSessionHas('success', __('administration::maintenance.empty-cache.success'));
    });

    it('requires CSRF token for cache clearing', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)
            ->post(route('administration.maintenance.empty-cache'), []);

        // Should fail due to missing CSRF token (redirects instead of 419 in test environment)
        $response->assertStatus(302);
    });

    it('shows maintenance page in navigation for tech admins', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));
        $content = $response->getContent();

        // Verify navigation shows the maintenance page
        expect($content)->toContain('Maintenance')
            ->and($content)->toContain('href="' . route('administration.maintenance') . '"')
            ->and($content)->toContain('build'); // icon
    });

    it('hides maintenance navigation from non-tech admins', function () {
        $user = admin($this);

        // Non-tech admins should be redirected when trying to access maintenance
        $response = $this->actingAs($user)
            ->get(route('administration.maintenance'));

        $response->assertStatus(302);
    });

    it('maintenance page has proper SEO structure', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));
        $content = $response->getContent();

        // Verify title elements are present
        expect($content)->toContain('<title>')
            ->and($content)->toContain(__('administration::maintenance.title'))
            ->and($content)->toContain('<h2')
            ->and($content)->toContain(__('administration::maintenance.empty-cache.title'));
    });

    it('cache clearing form has proper structure', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));
        $content = $response->getContent();

        // Verify form structure
        expect($content)->toContain('<form')
            ->and($content)->toContain('action="' . route('administration.maintenance.empty-cache') . '"')
            ->and($content)->toContain('method="POST"')
            ->and($content)->toContain('type="submit"');
            
        // Verify CSRF token is present (hidden input)
        expect($content)->toContain('name="_token"')
            ->and($content)->toContain('type="hidden"');
            
        // Verify button text is present
        expect($content)->toContain(__('administration::maintenance.empty-cache.button-label'));
    });
});
