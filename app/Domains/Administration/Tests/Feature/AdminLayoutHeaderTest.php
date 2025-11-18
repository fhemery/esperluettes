<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin layout header', function () {
    beforeEach(function () {
        $this->registry = app(AdminNavigationRegistry::class);
        $this->registry->clear();
        
        // Register a test navigation item
        $this->registry->registerGroup('moderation', 'Moderation', 10);
        $this->registry->registerPage(
            'user-management',
            'moderation',
            'User Management',
            route('moderation.admin.user-management'),
            'people',
            [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
            10,
            'custom'
        );
    });

    it('admin layout header contains logo linking to dashboard', function () {
        $user = admin($this);

        $response = $this->actingAs($user)->get(route('moderation.admin.user-management'));
        $content = $response->getContent();

        // Check for logo image
        expect($content)->toContain('header-logo')
            ->and($content)->toContain('logo.png')
            ->and($content)->toContain('alt="' . config('app.name') . '"');

        // Check that logo is wrapped in a link to dashboard route
        expect($content)->toContain('href="' . route('dashboard') . '"');
    });

    it('admin layout header contains back-to-site button', function () {
        $user = admin($this);

        $response = $this->actingAs($user)->get(route('moderation.admin.user-management'));
        $content = $response->getContent();

        // Check for back-to-site text
        expect($content)->toContain(__('administration::navigation.back-to-site'));

        // Check that back-to-site button links to dashboard route
        expect($content)->toContain('href="' . route('dashboard') . '"');
    });

    it('admin layout header has proper navigation structure', function () {
        $user = moderator($this);

        $response = $this->actingAs($user)->get(route('moderation.admin.user-management'));
        $content = $response->getContent();

        // Check for nav element with proper structure
        expect($content)->toContain('<nav')
            ->and($content)->toContain('max-w-7xl mx-auto')
            ->and($content)->toContain('flex justify-between h-16');

        // Check for primary background div
        expect($content)->toContain('bg-primary w-full');
    });
});
