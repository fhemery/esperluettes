<?php

use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Private\Services\StaticPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows access to published static page via catch-all route', function () {
    $admin = admin($this);
    $page = StaticPage::factory()->published()->create([
        'title' => 'FAQ',
        'slug' => 'faq',
        'created_by' => $admin->id,
    ]);

    $response = $this->get('/' . $page->slug);
    $response->assertOk();
    $response->assertSee('FAQ');
});

it('returns 404 for draft page to guests', function () {
    $admin = admin($this);
    $page = StaticPage::factory()->create([
        'title' => 'Draft Only',
        'slug' => 'draft-only',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    Auth::logout();

    $response = $this->get('/' . $page->slug);
    $response->assertNotFound();
});

it('returns 404 for draft page to non-admins', function () {
    $admin = admin($this);
    $page = StaticPage::factory()->create([
        'title' => 'Draft Only',
        'slug' => 'draft-only',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $user = alice($this);
    $this->actingAs($user);


    $response = $this->get('/' . $page->slug);
    $response->assertNotFound();
});

it('shows draft preview with banner to admins', function () {
    $admin = admin($this);
    $page = StaticPage::factory()->create([
        'title' => 'Legal Notice',
        'slug' => 'legal-notice',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get('/' . $page->slug);
    $response->assertOk();
    $response->assertSee('static::public.draft_preview');
    $response->assertSee('Legal Notice');
});

it('shows draft preview with banner to tech-admins', function () {
    $tech = techAdmin($this);
    $page = StaticPage::factory()->create([
        'title' => 'Tech Draft',
        'slug' => 'tech-draft',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $tech->id,
    ]);

    $response = $this->actingAs($tech)->get('/' . $page->slug);
    $response->assertOk();
    $response->assertSee('static::public.draft_preview');
    $response->assertSee('Tech Draft');
});

it('rebuilds slug map cache on CRUD and status changes', function () {
    $admin = admin($this);

    // Create published page -> should appear in cache
    $page = StaticPage::factory()->published()->create([
        'slug' => 'about',
        'created_by' => $admin->id,
    ]);

    $service = app(StaticPageService::class);
    $map = Cache::get(StaticPageService::CACHE_KEY_SLUG_MAP) ?? $service->getSlugMap();
    expect($map)->toHaveKey('about');
    expect($map['about'])->toEqual($page->id);

    // Update slug -> cache should update
    $page->slug = 'about-us';
    $page->save();
    $map = Cache::get(StaticPageService::CACHE_KEY_SLUG_MAP);
    expect($map)->toHaveKey('about-us');
    expect($map)->not()->toHaveKey('about');

    // Unpublish -> removed from cache
    $service->unpublish($page);
    $map = Cache::get(StaticPageService::CACHE_KEY_SLUG_MAP);
    expect($map)->not()->toHaveKey('about-us');

    // Publish again -> back in cache
    $service->publish($page);
    $map = Cache::get(StaticPageService::CACHE_KEY_SLUG_MAP);
    expect($map)->toHaveKey('about-us');

    // Delete -> removed from cache
    $page->delete();
    $map = Cache::get(StaticPageService::CACHE_KEY_SLUG_MAP);
    expect($map)->not()->toHaveKey('about-us');
});
