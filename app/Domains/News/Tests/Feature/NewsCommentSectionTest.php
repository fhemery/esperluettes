<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('News comment section on the article page', function () {
    it('renders the comment thread on a published article for a logged-in user', function () {
        $author = admin($this);
        $news = News::factory()->published()->create([
            'title' => 'Commented Article',
            'slug' => 'commented-article',
            'created_by' => $author->id,
        ]);

        $this->actingAs(alice($this));

        $response = $this->get(route('news.show', ['slug' => $news->slug]));

        $response->assertOk();
        $response->assertSee('id="comment-list"', false);
        $response->assertSee("entityType: 'news'", false);
    });

    it('does not render the comment thread on a draft article previewed by an admin', function () {
        $author = admin($this);
        $news = News::factory()->create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $author->id,
        ]);

        $response = $this->actingAs($author)->get(route('news.show', ['slug' => $news->slug]));

        $response->assertOk();
        $response->assertDontSee('id="comment-list"', false);
        $response->assertDontSee("entityType: 'news'", false);
    });

    it('still 404s a draft article for a regular user', function () {
        $author = admin($this);
        $news = News::factory()->create([
            'title' => 'Hidden Draft',
            'slug' => 'hidden-draft',
            'status' => 'draft',
            'published_at' => null,
            'created_by' => $author->id,
        ]);

        $this->actingAs(alice($this));

        $this->get(route('news.show', ['slug' => $news->slug]))->assertNotFound();
    });

    it('shows the comment form to a confirmed user on a published article', function () {
        $author = admin($this);
        $news = News::factory()->published()->create([
            'title' => 'Open For Comments',
            'slug' => 'open-for-comments',
            'created_by' => $author->id,
        ]);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        $response = $this->get(route('news.show', ['slug' => $news->slug]));

        $response->assertOk();
        $response->assertSee('action="' . route('comments.store') . '"', false);
        // Lazy mode: config + total only, no items in the initial HTML.
        $response->assertSee('page: 0,', false);
    });

    it('shows the members-only prompt to a guest', function () {
        // Documents Comment's existing checkAccess() behaviour (decision #10):
        // logged-out visitors get the members-only box, not the thread.
        $author = admin($this);
        $news = News::factory()->published()->create([
            'title' => 'Guest View',
            'slug' => 'guest-view',
            'created_by' => $author->id,
        ]);

        Auth::logout();

        $response = $this->get(route('news.show', ['slug' => $news->slug]));

        $response->assertOk();
        $response->assertSee('comment::comments.errors.members_only');
    });
});
