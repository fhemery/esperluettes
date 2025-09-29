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

it('renders bienvenue data for authenticated users on dashboard view (no mocks)', function () {
    $user = alice($this);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();

    $content = $response->getContent();

    // Should render the welcome card and generic labels (via translations)
    expect($content)
        ->toContain('surface-read')
        ->and($content)->toContain(__('dashboard::welcome.welcome_message'))
        ->and($content)->toContain(__('dashboard::welcome.member_since'))
        ->and($content)->toContain(__('dashboard::welcome.role_label'))
        ->and($content)->toContain(__('dashboard::welcome.activity_summary'));
});

it('renders Keep Reading widget with empty state on dashboard', function () {
    $user = alice($this);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();

    $content = $response->getContent();
    expect($content)
        ->toContain(__('story::keep-reading.title'))
        ->and($content)->toContain(__('story::keep-reading.empty'));
});

it('renders Keep Writing widget with empty state on dashboard', function () {
    $user = alice($this);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();

    $content = $response->getContent();
    expect($content)
        ->toContain(__('story::keep-writing.title'))
        ->and($content)->toContain(__('story::keep-writing.empty'));
});
