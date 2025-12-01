<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUnpublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('News Admin Controller', function () {
    describe('index', function () {
        it('displays the news list for admin users', function () {
            $user = admin($this);
            News::factory()->create(['title' => 'First News', 'status' => 'published']);
            News::factory()->create(['title' => 'Draft News', 'status' => 'draft']);

            $response = $this->actingAs($user)
                ->get(route('news.admin.index'));

            $response->assertOk();
            $response->assertSee('First News');
            $response->assertSee('Draft News');
        });

        it('denies access to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->get(route('news.admin.index'));

            $response->assertRedirect(route('dashboard'));
        });

        it('redirects unauthenticated users to login', function () {
            $response = $this->get(route('news.admin.index'));

            $response->assertRedirect(route('login'));
        });
    });

    describe('create', function () {
        it('displays the create form', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('news.admin.create'));

            $response->assertOk();
        });
    });

    describe('store', function () {
        it('creates a draft news item', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('news.admin.store'), [
                    'title' => 'Test News',
                    'slug' => 'test-news',
                    'summary' => 'A short summary',
                    'content' => '<p>News content</p>',
                    'status' => 'draft',
                    'is_pinned' => false,
                ]);

            $response->assertRedirect(route('news.admin.index'));

            $this->assertDatabaseHas('news', [
                'title' => 'Test News',
                'slug' => 'test-news',
                'status' => 'draft',
                'created_by' => $user->id,
            ]);
        });

        it('creates and publishes a news item', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('news.admin.store'), [
                    'title' => 'Published News',
                    'slug' => 'published-news',
                    'summary' => 'A short summary',
                    'content' => '<p>News content</p>',
                    'status' => 'published',
                    'is_pinned' => false,
                ]);

            $response->assertRedirect(route('news.admin.index'));

            $news = News::where('slug', 'published-news')->first();
            expect($news)->not->toBeNull();
            expect($news->status)->toBe('published');
            expect($news->published_at)->not->toBeNull();

            // Check event was emitted
            $event = latestEventOf(NewsPublished::name(), NewsPublished::class);
            expect($event)->not->toBeNull();
            expect($event->slug)->toBe('published-news');
        });

        it('validates required fields', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('news.admin.store'), [
                    'title' => '',
                    'slug' => '',
                    'summary' => '',
                    'content' => '',
                    'status' => '',
                ]);

            $response->assertSessionHasErrors(['title', 'slug', 'summary', 'content', 'status']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            News::factory()->create(['slug' => 'existing-slug']);

            $response = $this->actingAs($user)
                ->post(route('news.admin.store'), [
                    'title' => 'New News',
                    'slug' => 'existing-slug',
                    'summary' => 'A summary',
                    'content' => '<p>Content</p>',
                    'status' => 'draft',
                ]);

            $response->assertSessionHasErrors('slug');
        });

        it('validates slug format', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('news.admin.store'), [
                    'title' => 'Invalid Slug Test',
                    'slug' => 'Invalid_Slug!',
                    'summary' => 'A summary',
                    'content' => '<p>Content</p>',
                    'status' => 'draft',
                ]);

            $response->assertSessionHasErrors('slug');
        });

        it('uploads a header image', function () {
            Storage::fake('public');
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('news.admin.store'), [
                    'title' => 'News With Image',
                    'slug' => 'news-with-image',
                    'summary' => 'A summary',
                    'content' => '<p>Content</p>',
                    'status' => 'draft',
                    'header_image' => UploadedFile::fake()->image('header.jpg', 800, 400),
                ]);

            $response->assertRedirect(route('news.admin.index'));

            $news = News::where('slug', 'news-with-image')->first();
            expect($news->header_image_path)->not->toBeNull();
        });
    });

    describe('edit', function () {
        it('displays the edit form with existing data', function () {
            $user = admin($this);
            $news = News::factory()->create([
                'title' => 'Existing News',
                'summary' => 'Existing summary',
            ]);

            $response = $this->actingAs($user)
                ->get(route('news.admin.edit', $news));

            $response->assertOk();
            $response->assertSee('Existing News');
        });
    });

    describe('update', function () {
        it('updates a news item', function () {
            $user = admin($this);
            $news = News::factory()->create([
                'title' => 'Old Title',
                'slug' => 'old-slug',
                'status' => 'draft',
            ]);

            $response = $this->actingAs($user)
                ->put(route('news.admin.update', $news), [
                    'title' => 'New Title',
                    'slug' => 'new-slug',
                    'summary' => 'Updated summary',
                    'content' => '<p>Updated content</p>',
                    'status' => 'draft',
                ]);

            $response->assertRedirect(route('news.admin.index'));

            $this->assertDatabaseHas('news', [
                'id' => $news->id,
                'title' => 'New Title',
                'slug' => 'new-slug',
            ]);
        });

        it('allows same slug when updating own record', function () {
            $user = admin($this);
            $news = News::factory()->create([
                'title' => 'My News',
                'slug' => 'my-slug',
            ]);

            $response = $this->actingAs($user)
                ->put(route('news.admin.update', $news), [
                    'title' => 'My Updated News',
                    'slug' => 'my-slug', // Same slug
                    'summary' => 'Updated summary',
                    'content' => '<p>Updated content</p>',
                    'status' => 'draft',
                ]);

            $response->assertRedirect(route('news.admin.index'));
            $response->assertSessionHasNoErrors();
        });
    });

    describe('publish', function () {
        it('publishes a draft news item', function () {
            $user = admin($this);
            $news = News::factory()->create([
                'title' => 'Draft to Publish',
                'status' => 'draft',
                'published_at' => null,
            ]);

            $response = $this->actingAs($user)
                ->patch(route('news.admin.publish', $news));

            $response->assertRedirect(route('news.admin.index'));
            $response->assertSessionHas('success');

            expect($news->fresh()->status)->toBe('published');
            expect($news->fresh()->published_at)->not->toBeNull();

            // Check event
            $event = latestEventOf(NewsPublished::name(), NewsPublished::class);
            expect($event)->not->toBeNull();
        });
    });

    describe('unpublish', function () {
        it('unpublishes a published news item', function () {
            $user = admin($this);
            $news = News::factory()->create([
                'title' => 'Published to Draft',
                'status' => 'published',
                'published_at' => now(),
            ]);

            $response = $this->actingAs($user)
                ->patch(route('news.admin.unpublish', $news));

            $response->assertRedirect(route('news.admin.index'));
            $response->assertSessionHas('success');

            expect($news->fresh()->status)->toBe('draft');

            // Check event
            $event = latestEventOf(NewsUnpublished::name(), NewsUnpublished::class);
            expect($event)->not->toBeNull();
        });
    });

    describe('destroy', function () {
        it('deletes a news item', function () {
            $user = admin($this);
            $news = News::factory()->create(['title' => 'To Delete']);

            $response = $this->actingAs($user)
                ->delete(route('news.admin.destroy', $news));

            $response->assertRedirect(route('news.admin.index'));
            $this->assertDatabaseMissing('news', ['id' => $news->id]);
        });
    });
});
