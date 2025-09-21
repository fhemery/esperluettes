<?php

use App\Domains\News\Private\Models\News;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows published news in index with correct link', function () {
    $admin = admin($this);
    $a = News::factory()->published()->create([
        'title' => 'Published News',
        'slug' => 'published-news',
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('news.index'));
    $response->assertOk();

    $response->assertSee('Published News');
    $response->assertSee('/news/' . $a->slug, escape: false);
});

it('does not show non-published news in index', function () {
    $admin = admin($this);
    News::factory()->create([
        'title' => 'Draft News',
        'slug' => 'draft-news',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('news.index'));
    $response->assertOk();

    $response->assertDontSee('Draft News');
});

it('allows direct access to published news', function () {
    $admin = admin($this);
    $a = News::factory()->published()->create([
        'title' => 'Published Detail',
        'slug' => 'published-detail',
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('news.show', $a->slug));
    $response->assertOk();
    $response->assertSee('Published Detail');
});

it('returns 404 for direct access to non-published news', function () {
    $admin = admin($this);
    $a = News::factory()->create([
        'title' => 'Draft Detail',
        'slug' => 'draft-detail',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $alice = alice($this);

    $response = $this->actingAs($alice)->get(route('news.show', $a->slug));
    $response->assertNotFound();
});
