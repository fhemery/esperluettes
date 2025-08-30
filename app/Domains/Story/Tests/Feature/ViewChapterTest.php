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

it('shows navigation among published chapters for readers with disabled edges', function () {
    $author = alice($this);
    $story = publicStory('Nav Story', $author->id);

    // Create three published chapters in order
    $c1 = createPublishedChapter($this, $story, $author, ['title' => 'C1']);
    $c2 = createPublishedChapter($this, $story, $author, ['title' => 'C2']);
    $c3 = createPublishedChapter($this, $story, $author, ['title' => 'C3']);

    // Guest on first chapter: no prev link (disabled), next points to C2
    $resp1 = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c1->slug]));
    $resp1->assertOk();
    // Ensure there is no clickable previous anchor (by aria-label)
    $resp1->assertDontSee('aria-label="story::chapters.navigation.previous"');
    $resp1->assertSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c2->slug]));

    // Guest on last chapter: prev points to C2, no next link (disabled)
    $resp3 = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c3->slug]));
    $resp3->assertOk();
    $resp3->assertSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c2->slug]));
    // Ensure there is no clickable next anchor (by aria-label)
    $resp3->assertDontSee('aria-label="story::chapters.navigation.next"');
});

it('includes unpublished chapters in navigation for authors', function () {
    $author = alice($this);
    $this->actingAs($author);
    $story = createStoryForAuthor($author->id, ['title' => 'Draft Nav']);

    $c1 = createPublishedChapter($this, $story, $author, ['title' => 'C1']);
    $c2 = createUnpublishedChapter($this, $story, $author, ['title' => 'Draft C2']);
    $c3 = createPublishedChapter($this, $story, $author, ['title' => 'C3']);

    // On C1, next should point to C2 (unpublished)
    $resp1 = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c1->slug]));
    $resp1->assertOk();
    $resp1->assertSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c2->slug]));

    // On C2 (unpublished), prev should point to C1 and next to C3
    $resp2 = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c2->slug]));
    $resp2->assertOk();
    $resp2->assertSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c1->slug]));
    $resp2->assertSee(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c3->slug]));
});

describe('Reading progress', function () {

    it('does not show the read button to the author', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

        $resp = $this->actingAs($author)
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
        $resp->assertDontSee('id="markReadToggle"', false);
    });

    it('shows unread read-button for a non-author user who has not read the chapter', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

        $reader = bob($this); // confirmed user by default
        $resp = $this->actingAs($reader)
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
        $resp->assertSee('id="markReadToggle"', false);
        $resp->assertSee(trans('story::chapters.actions.mark_as_read'));
    });

    it('shows read-state button for a non-author user who has already read the chapter', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Pub Chap']);

        $reader = bob($this);
        // Mark as read via API to reflect real behavior
        $this->actingAs($reader)
            ->post(route('chapters.read.mark', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertNoContent();

        $resp = $this->actingAs($reader)
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
        $resp->assertSee('id="markReadToggle"', false);
        $resp->assertSee(trans('story::chapters.actions.marked_read'));
    });
});

describe('Reading statistics', function () {
    it('shows reads counter on chapter details page (non-zero values)', function () {
        $author = alice($this);
        $story = publicStory('Details Reads', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Details Chapter']);
    
        $reader = bob($this);
    
        $this->actingAs($reader); markAsRead($this, $chapter)->assertNoContent();
    
        $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();
    
        $resp->assertSee(trans('story::chapters.reads.label'));
        $resp->assertSee(trans('story::chapters.reads.tooltip'));
    
        $resp->assertSee('1');
    });
});

describe('regarding SEO', function () {
    it('301-redirects when chapter slug base is outdated', function () {
        $author = alice($this);
        $story = publicStory('SEO Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'SEO Chapter']);

        // Build a wrong base but same id for chapter slug
        $wrongChapterSlug = 'old-chapter-' . $chapter->id;

        $resp = $this->get(route('chapters.show', [
            'storySlug' => $story->slug,
            'chapterSlug' => $wrongChapterSlug,
        ]));
        $resp->assertStatus(301);
        $resp->assertRedirect(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    });

    it('301-redirects when story slug base is outdated', function () {
        $author = alice($this);
        $story = publicStory('SEO Story 2', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'SEO Chapter 2']);

        // Build a wrong base but same id for story slug
        $wrongStorySlug = 'old-story-' . $story->id;

        $resp = $this->get(route('chapters.show', [
            'storySlug' => $wrongStorySlug,
            'chapterSlug' => $chapter->slug,
        ]));
        $resp->assertStatus(301);
        $resp->assertRedirect(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
    });

    it('301-redirects once to combined canonical when both story and chapter bases are outdated and preserves query string', function () {
        $author = alice($this);
        $story = publicStory('SEO Story 3', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'SEO Chapter 3']);

        $wrongStorySlug = 'ancient-story-' . $story->id;
        $wrongChapterSlug = 'ancient-chapter-' . $chapter->id;

        $url = route('chapters.show', [
            'storySlug' => $wrongStorySlug,
            'chapterSlug' => $wrongChapterSlug,
        ]) . '?utm=abc&ref=xyz';

        $resp = $this->get($url);
        $resp->assertStatus(301);

        $location = $resp->headers->get('Location');
        $baseExpected = route('chapters.show', [
            'storySlug' => $story->slug,
            'chapterSlug' => $chapter->slug,
        ]);
        $this->assertStringStartsWith($baseExpected, $location);

        $parsed = parse_url($location);
        parse_str($parsed['query'] ?? '', $qs);
        $this->assertEquals(['utm' => 'abc', 'ref' => 'xyz'], $qs);
    });

    it('renders SEO meta tags for chapter page', function () {
        $author = alice($this);
        $story = publicStory('SEO Story Title', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'SEO Chapter Title']);

        $resp = $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $resp->assertOk();

        $expectedTitle = 'SEO Story Title — SEO Chapter Title';

        // Title tag from layout
        $resp->assertSee('<title>' . $expectedTitle . '</title>', false);

        // OG/Twitter title tags
        $resp->assertSee('property="og:title" content="' . $expectedTitle . '"', false);
        $resp->assertSee('name="twitter:title" content="' . $expectedTitle . '"', false);

        // Default cover image used in both OG and Twitter
        $resp->assertSee('/images/story/default-cover.svg', false);

        // No meta description for chapter page per US-041
        $resp->assertDontSee('name="description"', false);
    });
});