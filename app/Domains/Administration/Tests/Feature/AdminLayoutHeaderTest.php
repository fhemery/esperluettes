<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin layout header', function () {
    it('admin layout header contains logo linking to dashboard', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));
        $content = $response->getContent();

        // Check for logo image
        expect($content)->toContain('header-logo')
            ->and($content)->toContain('logo.png')
            ->and($content)->toContain('alt="' . config('app.name') . '"');

        // Check that logo is wrapped in a link to dashboard route
        expect($content)->toContain('href="' . route('dashboard') . '"');
    });

    it('admin layout header contains back-to-site button', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));
        $content = $response->getContent();

        // Check for back-to-site text
        expect($content)->toContain(__('administration::navigation.back-to-site'));

        // Check that back-to-site button links to dashboard route
        expect($content)->toContain('href="' . route('dashboard') . '"');
    });

    it('admin layout header has proper navigation structure', function () {
        $user = techAdmin($this);

        $response = $this->actingAs($user)->get(route('administration.maintenance'));
        $content = $response->getContent();

        // Check for nav element with proper structure
        expect($content)->toContain('<nav', false);
    });
});
