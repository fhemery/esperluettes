<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Public\Events\StaticPagePublished;
use App\Domains\StaticPage\Public\Events\StaticPageUnpublished;
use App\Domains\StaticPage\Public\Events\StaticPageDeleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Static Page Admin Controller', function () {
    describe('index', function () {
        it('displays the page list for admin users', function () {
            $user = admin($this);
            StaticPage::factory()->create(['title' => 'About Page', 'status' => 'published']);
            StaticPage::factory()->create(['title' => 'Draft Page', 'status' => 'draft']);

            $response = $this->actingAs($user)
                ->get(route('static.admin.index'));

            $response->assertOk();
            $response->assertSee('About Page');
            $response->assertSee('Draft Page');
        });

        it('denies access to non-admin users', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $response = $this->actingAs($user)
                ->get(route('static.admin.index'));

            $response->assertRedirect(route('dashboard'));
        });

        it('redirects unauthenticated users to login', function () {
            $response = $this->get(route('static.admin.index'));

            $response->assertRedirect(route('login'));
        });
    });

    describe('create', function () {
        it('displays the create form', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->get(route('static.admin.create'));

            $response->assertOk();
            $response->assertViewIs('static::pages.admin.create');
        });
    });

    describe('store', function () {
        it('creates a new static page', function () {
            $user = admin($this);
            $data = [
                'title' => 'Test Page',
                'slug' => 'test-page',
                'summary' => 'A test summary',
                'content' => '<p>Test content</p>',
                'status' => 'draft',
            ];

            $response = $this->actingAs($user)
                ->post(route('static.admin.store'), $data);

            $response->assertRedirect(route('static.admin.index'));
            $this->assertDatabaseHas('static_pages', [
                'title' => 'Test Page',
                'slug' => 'test-page',
                'status' => 'draft',
            ]);
        });

        it('validates required fields', function () {
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('static.admin.store'), []);

            $response->assertSessionHasErrors(['title', 'slug', 'content', 'status']);
        });

        it('validates slug uniqueness', function () {
            $user = admin($this);
            StaticPage::factory()->create(['slug' => 'existing-page']);

            $response = $this->actingAs($user)
                ->post(route('static.admin.store'), [
                    'title' => 'New Page',
                    'slug' => 'existing-page',
                    'content' => '<p>Content</p>',
                    'status' => 'draft',
                ]);

            $response->assertSessionHasErrors(['slug']);
        });

        it('uploads header image', function () {
            Storage::fake('public');
            $user = admin($this);

            $response = $this->actingAs($user)
                ->post(route('static.admin.store'), [
                    'title' => 'Page With Image',
                    'slug' => 'page-with-image',
                    'content' => '<p>Content</p>',
                    'status' => 'draft',
                    'header_image' => UploadedFile::fake()->image('header.jpg', 800, 400),
                ]);

            $response->assertRedirect(route('static.admin.index'));
            $page = StaticPage::where('slug', 'page-with-image')->first();
            expect($page->header_image_path)->not->toBeNull();
        });
    });

    describe('edit', function () {
        it('displays the edit form', function () {
            $user = admin($this);
            $page = StaticPage::factory()->create();

            $response = $this->actingAs($user)
                ->get(route('static.admin.edit', $page));

            $response->assertOk();
            $response->assertViewIs('static::pages.admin.edit');
            $response->assertViewHas('page');
        });
    });

    describe('update', function () {
        it('updates an existing page', function () {
            $user = admin($this);
            $page = StaticPage::factory()->create(['title' => 'Old Title']);

            $response = $this->actingAs($user)
                ->put(route('static.admin.update', $page), [
                    'title' => 'New Title',
                    'slug' => $page->slug,
                    'content' => '<p>Updated content</p>',
                    'status' => 'draft',
                ]);

            $response->assertRedirect(route('static.admin.index'));
            $this->assertDatabaseHas('static_pages', [
                'id' => $page->id,
                'title' => 'New Title',
            ]);
        });

        it('allows same slug on update for same page', function () {
            $user = admin($this);
            $page = StaticPage::factory()->create(['slug' => 'my-page']);

            $response = $this->actingAs($user)
                ->put(route('static.admin.update', $page), [
                    'title' => 'Updated Title',
                    'slug' => 'my-page',
                    'content' => '<p>Content</p>',
                    'status' => 'draft',
                ]);

            $response->assertRedirect(route('static.admin.index'));
            $response->assertSessionHasNoErrors();
        });
    });

    describe('publish', function () {
        it('publishes a draft page and emits event', function () {
            $user = admin($this);
            $page = StaticPage::factory()->create(['status' => 'draft']);

            $response = $this->actingAs($user)
                ->patch(route('static.admin.publish', $page));

            $response->assertRedirect(route('static.admin.index'));
            expect($page->fresh()->status)->toBe('published');

            $event = latestEventOf(StaticPagePublished::name(), StaticPagePublished::class);
            expect($event)->not->toBeNull();
            expect($event->pageId)->toBe($page->id);
        });
    });

    describe('unpublish', function () {
        it('unpublishes a published page and emits event', function () {
            $user = admin($this);
            $page = StaticPage::factory()->create(['status' => 'published']);

            $response = $this->actingAs($user)
                ->patch(route('static.admin.unpublish', $page));

            $response->assertRedirect(route('static.admin.index'));
            expect($page->fresh()->status)->toBe('draft');

            $event = latestEventOf(StaticPageUnpublished::name(), StaticPageUnpublished::class);
            expect($event)->not->toBeNull();
            expect($event->pageId)->toBe($page->id);
        });
    });

    describe('destroy', function () {
        it('deletes a page and emits event', function () {
            $user = admin($this);
            $page = StaticPage::factory()->create();
            $pageId = $page->id;

            $response = $this->actingAs($user)
                ->delete(route('static.admin.destroy', $page));

            $response->assertRedirect(route('static.admin.index'));
            $this->assertDatabaseMissing('static_pages', ['id' => $pageId]);

            $event = latestEventOf(StaticPageDeleted::name(), StaticPageDeleted::class);
            expect($event)->not->toBeNull();
            expect($event->pageId)->toBe($pageId);
        });
    });
});
