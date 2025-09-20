<?php

use App\Domains\Admin\Filament\Resources\News\NewsResource\Pages\CreateNews;
use App\Domains\Admin\Filament\Resources\News\NewsResource\Pages\EditNews;
use App\Domains\News\Private\Models\News;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a pinned published news and auto-assigns display order via create page', function () {
    $admin = admin($this);
    $this->actingAs($admin);

    $title = 'Created via Resource';

    Livewire::test(CreateNews::class)
        ->fillForm([
            'title' => $title,
            'slug' => 'created-via-resource',
            'summary' => 'Summary',
            'content' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => \Illuminate\Support\Carbon::now(),
            'is_pinned' => true,
            'display_order' => null,
            'meta_description' => 'Meta',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $a = News::query()->where('slug', 'created-via-resource')->first();

    expect($a)->not->toBeNull();
    expect($a->status)->toBe('published');
    expect((bool)$a->is_pinned)->toBeTrue();
    expect($a->display_order)->not->toBeNull();
});

it('unpinning via edit clears display order via edit page', function () {
    $admin = admin($this);
    $this->actingAs($admin);

    $a = News::factory()->published()->pinned()->create([
        'title' => 'Pinned initially',
        'slug' => 'pinned-initially',
        'display_order' => 5,
    ]);

    Livewire::test(EditNews::class, ['record' => $a->getKey()])
        ->fillForm([
            'title' => $a->title,
            'slug' => $a->slug,
            'summary' => $a->summary,
            'content' => $a->content,
            'status' => $a->status,
            'published_at' => $a->published_at,
            'meta_description' => $a->meta_description,
            'is_pinned' => false,
            'display_order' => $a->display_order,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $a->refresh();
    expect((bool)$a->is_pinned)->toBeFalse();
    expect($a->display_order)->toBeNull();
});

it('saves content via create page with target blank only on external links', function () {
    // Configure app URL for host comparison and isolate purifier cache
    config(['app.url' => 'https://example.com']);
    config(['purifier.cachePath' => storage_path('framework/testing/purifier')]);

    $admin = admin($this);
    $this->actingAs($admin);

    $title = 'News with links';
    $slug = 'news-with-links';
    $content = '<p>Internal <a href="https://example.com/about">About</a> and <a href="/contact">Contact</a>. External <a href="https://google.com">Google</a>.</p>';

    Livewire::test(CreateNews::class)
        ->fillForm([
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Summary',
            'content' => $content,
            'status' => 'draft',
            'meta_description' => 'Meta',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $saved = News::query()->where('slug', $slug)->firstOrFail();

    // External link should have target and rel
    expect($saved->content)
        ->toContain('<a href="https://google.com" target="_blank" rel="noopener noreferrer">Google</a>');

    // Internal absolute should not gain target
    expect($saved->content)
        ->toContain('<a href="https://example.com/about">About</a>');

    // Relative link should not gain target
    expect($saved->content)
        ->toContain('<a href="/contact">Contact</a>');
});
