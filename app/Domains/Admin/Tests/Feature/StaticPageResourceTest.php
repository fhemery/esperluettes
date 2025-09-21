<?php

use App\Domains\Admin\Filament\Resources\StaticPage\StaticPageResource\Pages\CreateStaticPage;
use App\Domains\StaticPage\Private\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('saves static page via create page with target blank only on external links', function () {
    // Configure app URL for host comparison and isolate purifier cache
    config(['app.url' => 'https://example.com']);
    config(['purifier.cachePath' => storage_path('framework/testing/purifier')]);

    $admin = admin($this);
    $this->actingAs($admin);

    $title = 'Page with links';
    $slug = 'page-with-links';
    $content = '<p>Internal <a href="https://example.com/help">Help</a> and <a href="/legal">Legal</a>. External <a href="https://wikipedia.org">Wiki</a>.</p>';

    Livewire::test(CreateStaticPage::class)
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

    $saved = StaticPage::query()->where('slug', $slug)->firstOrFail();

    // External link should have target and rel
    expect($saved->content)
        ->toContain('<a href="https://wikipedia.org" target="_blank" rel="noopener noreferrer">Wiki</a>');

    // Internal absolute should not gain target
    expect($saved->content)
        ->toContain('<a href="https://example.com/help">Help</a>');

    // Relative link should not gain target
    expect($saved->content)
        ->toContain('<a href="/legal">Legal</a>');
});
