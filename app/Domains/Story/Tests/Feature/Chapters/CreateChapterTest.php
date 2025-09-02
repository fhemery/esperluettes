<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('redirects guests from chapter create page to login', function () {
    $author = alice($this);
    $story = createStoryForAuthor($author->id, ['title' => 'Guest Blocked Story']);

    $resp = $this->get('/stories/' . $story->slug . '/chapters/create');
    $resp->assertRedirect('/login');
});

it('denies non-confirmed users from accessing chapter create page', function () {
    $author = alice($this);
    $nonConfirmed = bob($this, roles: [Roles::USER]);
    $this->actingAs($nonConfirmed);

    $story = createStoryForAuthor($author->id, ['title' => 'Blocked']);

    $resp = $this->get('/stories/' . $story->slug . '/chapters/create');
    // CheckRole middleware redirects unauthorized roles to dashboard
    $resp->assertRedirect(route('dashboard'));
});

it('allows an author to access chapter create page', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Writable']);

    $resp = $this->get('/stories/' . $story->slug . '/chapters/create');
    $resp->assertOk();
    $resp->assertSee(trans('story::chapters.create.title'));
});

it('creates a published chapter and redirects to its show page', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'My Story']);

    $payload = [
        'title' => 'Chapter One',
        'author_note' => '<p>Hello <strong>world</strong></p>',
        'content' => '<p>Body</p>',
        // published checkbox defaults to on; sending explicitly for clarity
        'published' => '1',
    ];

    $resp = $this->post('/stories/' . $story->slug . '/chapters', $payload);
    $resp->assertRedirect();

    $chapter = Chapter::query()->firstOrFail();
    expect($chapter->title)->toBe('Chapter One')
        ->and($chapter->status)->toBe(Chapter::STATUS_PUBLISHED)
        ->and($chapter->first_published_at)->not->toBeNull()
        ->and($chapter->slug)->toEndWith('-' . $chapter->id);

    // Show page should be reachable publicly since published
    $show = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $show->assertOk();
    $show->assertSee('Chapter One');
});

it('should work properly with twice the same chapter name', function() {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'My Story']);

    $payload = [
        'title' => 'Chapter 1',
        'author_note' => '<p>Hello <strong>world</strong></p>',
        'content' => '<p>Body</p>',
        // published checkbox defaults to on; sending explicitly for clarity
        'published' => '1',
    ];
    $this->post('/stories/' . $story->slug . '/chapters', $payload);
    $secondPost = $this->post('/stories/' . $story->slug . '/chapters', $payload);
    $secondPost->assertRedirect();
    
});

it('returns 404 when a non-author tries to create a chapter on someone else\'s story', function () {
    $author = alice($this);
    $other = bob($this);
    $this->actingAs($other);

    $story = createStoryForAuthor($author->id, ['title' => 'Alice\'s']);

    $payload = [
        'title' => 'Should Not Work',
        'content' => '<p>x</p>',
        'published' => '1',
    ];

    $resp = $this->post('/stories/' . $story->slug . '/chapters', $payload);
    $resp->assertNotFound();
    expect(Chapter::query()->count())->toBe(0);
});

it('shows validation error when title is empty', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Val Story']);

    $payload = [
        'title' => '',
        'content' => '<p>ok</p>',
        'published' => '1',
    ];

    $resp = $this->from('/stories/' . $story->slug . '/chapters/create')
        ->post('/stories/' . $story->slug . '/chapters', $payload);

    $resp->assertRedirect('/stories/' . $story->slug . '/chapters/create');
    $resp->assertSessionHasErrors(['title']);
    expect(Chapter::query()->count())->toBe(0);
});

it('shows validation error when title contains only whitespace', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Val Story']);

    $payload = [
        'title' => '   ',
        'content' => '<p>ok</p>',
    ];

    $resp = $this->from('/stories/' . $story->slug . '/chapters/create')
        ->post('/stories/' . $story->slug . '/chapters', $payload);

    $resp->assertRedirect('/stories/' . $story->slug . '/chapters/create');
    $resp->assertSessionHasErrors(['title']);
    expect(Chapter::query()->count())->toBe(0);
});

it('shows validation error when title is too long (>255)', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Val Story']);

    $payload = [
        'title' => str_repeat('a', 256),
        'content' => '<p>ok</p>',
    ];

    $resp = $this->from('/stories/' . $story->slug . '/chapters/create')
        ->post('/stories/' . $story->slug . '/chapters', $payload);

    $resp->assertRedirect('/stories/' . $story->slug . '/chapters/create');
    $resp->assertSessionHasErrors(['title']);
    expect(Chapter::query()->count())->toBe(0);
});

it('shows validation error when content is empty', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Val Story']);

    $payload = [
        'title' => 'Valid Title',
        'content' => '',
    ];

    $resp = $this->from('/stories/' . $story->slug . '/chapters/create')
        ->post('/stories/' . $story->slug . '/chapters', $payload);

    $resp->assertRedirect('/stories/' . $story->slug . '/chapters/create');
    $resp->assertSessionHasErrors(['content']);
    expect(Chapter::query()->count())->toBe(0);
});

// note: additional logical length validations may be added in service later; current tests
// focus on request rules and explicit service checks covered elsewhere.

it('accepts author_note logical length exactly 1000 characters', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Len Test OK']);

    $exactPlain = str_repeat('a', 1000);
    $payload = [
        'title' => 'Len Chap OK',
        'author_note' => '<p>' . $exactPlain . '</p>',
        'content' => '<p>ok</p>',
        'published' => '1',
    ];

    $resp = $this->from('/stories/' . $story->slug . '/chapters/create')
        ->post('/stories/' . $story->slug . '/chapters', $payload);

    $resp->assertRedirect();
    expect(Chapter::query()->count())->toBe(1);
});

it('validates that author_note logical length must be <= 1000 characters', function () {
    $user = alice($this);
    $this->actingAs($user);
    $story = createStoryForAuthor($user->id, ['title' => 'Len Test']);

    // Build an author_note that exceeds 1000 chars after strip_tags
    $longPlain = str_repeat('a', 1001);
    $payload = [
        'title' => 'Len Chap',
        'author_note' => '<p>' . $longPlain . '</p>',
        'content' => '<p>ok</p>',
        'published' => '1',
    ];

    $resp = $this->from('/stories/' . $story->slug . '/chapters/create')
        ->post('/stories/' . $story->slug . '/chapters', $payload);

    $resp->assertRedirect('/stories/' . $story->slug . '/chapters/create');
    $resp->assertSessionHasErrors(['author_note']);
    expect(Chapter::query()->count())->toBe(0);
});
