<?php

use App\Domains\Admin\Filament\Resources\News\PinnedNewsResource;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('excludes non-pinned published news from PinnedNewsResource', function () {
    $admin = admin($this);
    $this->actingAs($admin);

    $title = 'Published not pinned';
    News::factory()->published()->create([
        'title' => $title,
        'created_by' => $admin->id,
        'is_pinned' => false,
        'display_order' => null,
    ]);

    $response = $this->get(PinnedNewsResource::getUrl());
    if ($response->isRedirect()) { $response = $this->followRedirects($response); }
    $response->assertOk();

    // Not pinned should not appear in pinned list; assert via DB and HTML (best-effort)
    expect(News::query()->where('is_pinned', true)->count())->toBe(0);
    $response->assertDontSee($title);
});

it('includes pinned published news in PinnedNewsResource', function () {
    $admin = admin($this);
    $this->actingAs($admin);

    $title = 'Pinned + Published';
    $a = News::factory()->published()->pinned()->create([
        'title' => $title,
        'created_by' => $admin->id,
    ]);

    $response = $this->get(PinnedNewsResource::getUrl());
    if ($response->isRedirect()) { $response = $this->followRedirects($response); }
    $response->assertOk();

    // Assert present in pinned query and try to see title in HTML
    expect(News::query()->where('is_pinned', true)->pluck('id'))->toContain($a->id);
    $response->assertSee($title);
});

it('assigns a display order for pinned published news (auto-order)', function () {
    $admin = admin($this);
    $this->actingAs($admin);

    $title = 'Pinned without order';
    $a = News::factory()->published()->pinned(null)->create([
        'title' => $title,
        'created_by' => $admin->id,
        'display_order' => null,
    ]);

    $response = $this->get(PinnedNewsResource::getUrl());
    if ($response->isRedirect()) { $response = $this->followRedirects($response); }
    $response->assertOk();

    // Reload and check order assigned
    $a->refresh();
    expect($a->display_order)->not->toBeNull();
});
