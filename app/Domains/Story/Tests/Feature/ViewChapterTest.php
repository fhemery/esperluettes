<?php

use App\Domains\Story\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('shows a published chapter to non-authors', function () {
    $author = alice($this);
    $story = publicStory('Public Story', $author->id);

    // create chapter via helper (uses real HTTP endpoint)
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

    // guest can see
    Auth::logout();
    $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertOk();
    $resp->assertSee('Pub Chap');
});

it('not show a published chapter from a community story to guests', function () {
    $author = alice($this);
    $story = communityStory('Community Story', $author->id);

    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

    Auth::logout();
    $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertNotFound();
});

it('not show a published chapter from a community story to non confirmed users', function () {
    $author = alice($this);
    $story = communityStory('Community Story', $author->id);

    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

    $resp = $this->actingAs(bob($this, roles: ['user']))->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertNotFound();
});

it('not show a published chapter from a private story to non-collaborators', function () {
    $author = alice($this);
    $story = privateStory('Private Story', $author->id);

    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

    $resp = $this->actingAs(bob($this))->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertNotFound();
});

it('should show a published chapter from a private story to collaborators', function () {
    $author = alice($this);
    $story = privateStory('Private Story', $author->id);
    $collaborator = bob($this);

    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $collaborator->id,
        'role' => 'Collaborator',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

    $resp = $this->actingAs(bob($this))->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertOk();
    $resp->assertSee('Pub Chap');
});

it('should not show an unpublished chapter from a private story to collaborators', function () {
    $author = alice($this);
    $story = privateStory('Private Story', $author->id);
    $collaborator = bob($this);

    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $collaborator->id,
        'role' => 'Collaborator',
        'invited_by_user_id' => $author->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

    $resp = $this->actingAs(bob($this))->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertNotFound();
});

it('returns 404 for unpublished chapter to non-authors', function () {
    $author = alice($this);
    $story = publicStory('Hidden Story', $author->id);

    // create as unpublished via helper
    $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Unpub']);

    // guest
    Auth::logout();
    $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertNotFound();
});

it('allows author to view unpublished chapter', function () {
    $author = alice($this);
    $this->actingAs($author);
    $story = createStoryForAuthor($author->id, ['title' => 'Work In Progress']);

    $payload = ['title' => 'Draft', 'content' => '<p>ok</p>'];
    $this->post('/stories/' . $story->slug . '/chapters', $payload)->assertRedirect();
    $chapter = Chapter::query()->firstOrFail();

    $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertOk();
    $resp->assertSee('Draft');
});

it('shows a draft badge on unpublished chapter', function () {
    $author = alice($this);
    $story = publicStory('Draft Story', $author->id);

    $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Draft Chap']);

    // Author view
    $resp = $this->actingAs($author)->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertOk();
    $resp->assertSee(trans('story::chapters.list.draft'));
});

it('shows an edit link next to chapter title for authors only', function () {
    $author = alice($this);
    $story = publicStory('Editable Story', $author->id);
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Editable Chap']);

    // Author sees edit link
    $resp = $this->actingAs($author)->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp->assertOk();
    $resp->assertSee(route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));

    // Guest does not see edit link
    Auth::logout();
    $resp2 = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    $resp2->assertOk();
    $resp2->assertDontSee(route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
});
