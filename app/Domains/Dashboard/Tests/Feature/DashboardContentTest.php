<?php

declare(strict_types=1);

use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('displays news carousel on dashboard when pinned items exist', function () {
    $user = alice($this);
    $admin = admin($this);

    News::factory()->published()->pinned()->create([
        'title' => 'Dashboard Carousel News',
        'summary' => 'This news should appear in the dashboard carousel',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Dashboard Carousel News');
    $response->assertSee('carousel');
    $response->assertSee('aria-roledescription="carousel"', false);
});

it('does not display news carousel on dashboard when no pinned items exist', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertDontSee('carousel');
    $response->assertDontSee('aria-roledescription="carousel"');
});
