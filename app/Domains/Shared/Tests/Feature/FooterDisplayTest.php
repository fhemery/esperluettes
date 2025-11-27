<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('footer display', function () {
    it('should display version number as link when version page exists', function () {
        // Mock the Cache::remember call to return our test version
        Cache::shouldReceive('remember')
            ->once()
            ->with('app_version', 3600, \Closure::class)
            ->andReturn('1.2.3');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<a href="/versions" class="hover:text-primary">' . __('shared::footer.version') . ' 1.2.3</a>', false);
    });

    it('should not display version when version is unknown', function () {
        // Mock the Cache::remember call to return null (unknown version)
        Cache::shouldReceive('remember')
            ->once()
            ->with('app_version', 3600, \Closure::class)
            ->andReturn(null);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(__('shared::footer.version'));
    });

    it('should display copyright with current year', function () {
        Cache::shouldReceive('remember')
            ->once()
            ->with('app_version', 3600, \Closure::class)
            ->andReturn(null);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('&copy; ' . date('Y') . ' ' . config('app.name'), false);
        $response->assertSee(__('shared::footer.brand_description'));
    });
});
