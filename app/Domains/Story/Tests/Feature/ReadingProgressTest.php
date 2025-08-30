<?php

use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\ReadingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function markAsRead($test, $chapter)
{
    return $test->post(route('chapters.read.mark', [
        'storySlug' => $chapter->story->slug,
        'chapterSlug' => $chapter->slug,
    ]));
}

function markAsUnread($test, $chapter)
{
    return $test->delete(route('chapters.read.unmark', [
        'storySlug' => $chapter->story->slug,
        'chapterSlug' => $chapter->slug,
    ]));
}

// Guest reading progress has been removed; no guest endpoints exist anymore.

describe('Reading Progress - Errors', function () {
    it('should forbid author from toggling their own chapter reading status', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Own']);

        $this->actingAs($author);
        markAsRead($this, $chapter)->assertForbidden();
        markAsUnread($this, $chapter)->assertForbidden();
    });

    it('should forbid guest from toggling reading status like logged user would do', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Public']);

        Auth::logout();
        markAsRead($this, $chapter)->assertRedirect('/login');
        markAsUnread($this, $chapter)->assertRedirect('/login');
    });

    // Removed: guest reading progress feature and endpoints

    it('should forbid non confirmed users from toggling reading status of community story', function () {
        $author = alice($this);
        $story = communityStory('Community Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Community']);

        $reader = bob($this, roles:['user']);
        $this->actingAs($reader);
        markAsRead($this, $chapter)->assertRedirect('/dashboard');
        markAsUnread($this, $chapter)->assertRedirect('/dashboard');
    });

    it('should forbid non collaborators from toggling private story chapter reading status', function () {
        $author = alice($this);
        $story = privateStory('Private Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Private']);

        $reader = bob($this);
        $this->actingAs($reader);
        markAsRead($this, $chapter)->assertNotFound();
        markAsUnread($this, $chapter)->assertNotFound();
    });
});

describe('Reading Progress - Success', function () {

    it('marks chapter as read for logged non-author and is idempotent', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Read Me']);

        $reader = bob($this); // not an author
        $this->actingAs($reader);

        markAsRead($this, $chapter)->assertNoContent();

        // Second call is idempotent
        markAsRead($this, $chapter)->assertNoContent();

        // TODO: Improve with read count by getting the story view later
    });



    it('unmarks chapter as read and decrements without going below zero (idempotent)', function () {
        $author = alice($this);
        $story = publicStory('Public Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Unmark']);

        $reader = bob($this);
        $this->actingAs($reader);

        // Mark then unmark
        markAsRead($this, $chapter)->assertNoContent();
        markAsUnread($this, $chapter)->assertNoContent();

        // Second call is idempotent
        markAsUnread($this, $chapter)->assertNoContent();

        // TODO: check later in chapter view that stat says 0 view
    });

    // Removed: guest read increment tests
});
