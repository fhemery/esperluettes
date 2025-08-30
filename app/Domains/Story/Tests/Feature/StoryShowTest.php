<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Domains\Story\Models\Chapter;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('shows public story details with title, description, authors and creation date', function () {
    // Arrange: author and public story
    $author = alice($this);
    $story = publicStory('Public Story', $author->id, [
        'description' => '<p>Some description</p>',
    ]);

    // Act
    $response = $this->get('/stories/' . $story->slug);

    // Assert
    $response->assertOk();
    $response->assertSee('Public Story');
    $response->assertSee('Some description');
    $response->assertSee($author->name);

    // Date shown in Y-m-d for non-fr locale in tests
    $response->assertSee($story->created_at->format('Y-m-d'));
});

it('returns 404 for private story to non-author', function () {
    $author = alice($this);
    $nonAuthor = bob($this);
    $story = privateStory('Private Story', $author->id);

    $this->actingAs($nonAuthor);
    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('return 404 for guest for community story', function () {
    $author = alice($this);
    $story = communityStory('Community Story', $author->id);

    Auth::logout();

    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('returns 404 for community story to non-confirmed users', function () {
    $author = alice($this);
    $unverified = bob($this, roles: ['user']);
    $story = communityStory('Community Story', $author->id);

    $this->actingAs($unverified);
    $this->get('/stories/' . $story->slug)->assertNotFound();
});

it('shows placeholder when description is empty', function () {
    $author = alice($this);
    $story = publicStory('No Desc Story', $author->id, ['description' => '']);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('story::show.no_description');
});

it('lists multiple authors separated by comma', function () {
    $author1 = alice($this);
    $author2 = bob($this);
    $story = publicStory('Coauthored Story', $author1->id);

    // Attach second author on pivot
    DB::table('story_collaborators')->insert([
        'story_id' => $story->id,
        'user_id' => $author2->id,
        'role' => 'author',
        'invited_by_user_id' => $author1->id,
        'invited_at' => now(),
        'accepted_at' => now(),
    ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('Alice');
    $response->assertSee('Bob');
});

it('should add a link to author profile page', function () {
    $author = alice($this);
    $story = publicStory('Public Story', $author->id);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee('/profile/alice');
});

it('shows the story type when available', function () {
    $author = alice($this);
    $type = makeStoryType('Short Story');
    $story = publicStory('Typed Story', $author->id, [
        'story_ref_type_id' => $type->id,
    ]);
    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee(trans('story::shared.type.label'));
    $response->assertSee('Short Story');
});

it('shows the audience when available', function () {
    $author = alice($this);
    $aud = makeAudience('Teens');
    $story = publicStory('Audience Story', $author->id, [
        'story_ref_audience_id' => $aud->id,
    ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee(trans('story::shared.audience.label'));
    $response->assertSee('Teens');
});

it('shows the copyright when available', function () {
    $author = alice($this);
    $cr = makeCopyright('Public Domain');
    $story = publicStory('Copyrighted Story', $author->id, [
        'story_ref_copyright_id' => $cr->id,
    ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee(trans('story::shared.copyright.label'));
    $response->assertSee('Public Domain');
});

it('shows the feedback when available', function () {
    $author = alice($this);
    $fb = makeFeedback('Open to feedback');
    $story = publicStory('Feedback Visible', $author->id, [
        'story_ref_feedback_id' => $fb->id,
    ]);

    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSee(trans('story::shared.feedback.label'));
    $response->assertSee('Open to feedback');
});

it('shows the create chapter button to the story author', function () {
    $author = alice($this);
    $story = publicStory('Author Story', $author->id);

    // Author views their own story
    $this->actingAs($author);
    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    // Button text comes from chapters partial using this translation key
    $response->assertSee('story::chapters.sections.add_chapter');
    // And link points to chapters.create for this story
    $response->assertSee(route('chapters.create', ['storySlug' => $story->slug]));
});

it('does not show the create chapter button to non-authors', function () {
    $author = alice($this);
    $other = bob($this);
    $story = publicStory('Non Author Story', $author->id);

    // Logged-in non-author
    $this->actingAs($other);
    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertDontSee('story::chapters.sections.add_chapter');
    $response->assertDontSee(route('chapters.create', ['storySlug' => $story->slug]));
});

it('lists only published chapters to non authors', function () {
    $author = alice($this);
    $story = publicStory('Chapters Story', $author->id);

    // Create one published and one draft chapter
    createPublishedChapter($this, $story, $author, ['title'=>'Chapter 1 - Published']);
    createUnpublishedChapter($this, $story, $author, ['title'=>'Chapter 2 - Draft']);

    // Guest (no auth)
    Auth::logout();
    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    // Sees only published chapter title
    $response->assertSee('Chapter 1 - Published');
    $response->assertDontSee('Chapter 2 - Draft');
    // No draft chip
    $response->assertDontSee(trans('story::chapters.list.draft'));
});

it('shows all chapters with draft chip to the author', function () {
    $author = alice($this);
    $story = publicStory('Chapters Story 2', $author->id);

    createPublishedChapter($this, $story, $author, ['title'=>'P Chap']);
    createUnpublishedChapter($this, $story, $author, ['title'=>'D Chap']);

    $this->actingAs($author);
    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    // Author sees both
    $response->assertSee('P Chap');
    $response->assertSee('D Chap');
    // Draft chip visible
    $response->assertSee(trans('story::chapters.list.draft'));
});

it('appends newly created chapter at the end of the list', function () {
    // Arrange: public story with existing chapters
    $author = alice($this);
    $story = publicStory('Ordered Story', $author->id);

    // Create two published chapters via HTTP (ensures service logic is used)
    createPublishedChapter($this, $story, $author, ['title' => 'Chapter 1']);
    createPublishedChapter($this, $story, $author, ['title' => 'Chapter 2']);

    // Act: create a new chapter which should be appended (higher sort_order)
    createPublishedChapter($this, $story, $author, ['title' => 'Chapter 3']);

    // Assert: on the story page, chapters appear in order with the new one last
    Auth::logout(); // guest view is enough since all are published
    $response = $this->get('/stories/' . $story->slug);
    $response->assertOk();
    $response->assertSeeInOrder(['Chapter 1', 'Chapter 2', 'Chapter 3']);
});

describe('Reading statistics', function (){

    it('shows reads counter on story page for readers with non-zero values', function () {
        $author = alice($this);
        $story = publicStory('Reads Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'First Chapter']);

        $reader1 = bob($this);
        $this->actingAs($reader1);
        markAsRead($this, $chapter)->assertNoContent();

        $resp = $this->get('/stories/' . $story->slug);
        $resp->assertOk();

        $resp->assertSee(trans('story::chapters.reads.label'));
        $resp->assertSee(trans('story::chapters.reads.tooltip'));

        $resp->assertSee('First Chapter');
        $resp->assertSee('1');
    });

    it('shows reads counter on story page for authors (value present in initial data and popover)', function () {
        $author = alice($this);
        $story = publicStory('Author Reads', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch']);

        $reader = bob($this);

        $this->actingAs($reader);
        markAsRead($this, $chapter)->assertNoContent();

        $this->actingAs($author);
        $resp = $this->get('/stories/' . $story->slug);
        $resp->assertOk();

        $resp->assertSee(trans('story::chapters.reads.label'));
        $resp->assertSee(trans('story::chapters.reads.tooltip'));

        // The author list renders numbers via Alpine; ensure the initial payload contains the readsLogged key
        // We cannot rely on client-side rendering in tests, so just verify the JSON attribute includes the key
        $resp->assertSeeInOrder(['readsLogged', '1']);
    });
});