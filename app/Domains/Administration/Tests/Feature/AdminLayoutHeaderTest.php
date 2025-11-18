<?php

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin layout header', function () {
    beforeEach(function () {
        $this->registry = app(AdminNavigationRegistry::class);
        $this->registry->clear();
    });

    it('admin layout header contains logo linking to dashboard', function () {
        // Create a simple test page using the admin layout
        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Check for logo image
        expect($render)->toContain('header-logo')
            ->and($render)->toContain('logo.png')
            ->and($render)->toContain('alt="' . config('app.name') . '"');

        // Check that logo is wrapped in a link to dashboard route
        expect($render)->toContain('href="' . route('dashboard') . '"');
    });

    it('admin layout header contains back-to-site button', function () {
        // Create a simple test page using the admin layout
        $render = Blade::render('<x-admin::layout></x-admin::layout>');

        // Check for back-to-site text
        expect($render)->toContain(__('admin::navigation.back-to-site'));

        // Check that back-to-site button links to dashboard route
        expect($render)->toContain('href="' . route('dashboard') . '"');
    });
});
