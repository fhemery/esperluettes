<?php

use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows access to published news via route', function () {
    $admin = admin($this);
    $news = News::factory()->create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $admin->id,
    ]);

    $response = $this->get(route('news.show', ['slug' => $news->slug]));
    $response->assertOk();
    $response->assertSee('Hello World');
});

it('returns 404 for draft news to guests', function () {
    $admin = admin($this);
    $news = News::factory()->create([
        'title' => 'Draft News',
        'slug' => 'draft-news',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    Auth::logout();
    
    $response = $this->get(route('news.show', ['slug' => $news->slug]));
    $response->assertNotFound();
});

it('returns 404 for draft news to non-admins', function () {
    $admin = admin($this);
    $news = News::factory()->create([
        'title' => 'Draft News',
        'slug' => 'draft-news',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $user = alice($this);
    $this->actingAs($user);

    $response = $this->get(route('news.show', ['slug' => $news->slug]));
    $response->assertNotFound();
});

it('shows draft preview with banner to admins', function () {
    $admin = admin($this);
    $news = News::factory()->create([
        'title' => 'Banner Test',
        'slug' => 'banner-test',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('news.show', ['slug' => $news->slug]));
    $response->assertOk();
    $response->assertSee('news::public.draft_preview');
    $response->assertSee('Banner Test');
});
