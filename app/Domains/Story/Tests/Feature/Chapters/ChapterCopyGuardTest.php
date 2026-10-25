<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('Chapter copy guard', function () {
    it('guards the chapter body against copy for a guest', function () {
        assertCopyGuarded(viewChapterAs($this, null));
    });

    it('guards the chapter body against copy for a confirmed reader', function () {
        assertCopyGuarded(viewChapterAs($this, bob($this)));
    });

    it('guards the chapter body against copy for a beta reader collaborator', function () {
        assertCopyGuarded(viewChapterAs($this, bob($this), fn ($story, $v) => addCollaborator($story->id, $v->id, 'beta-reader')));
    });

    it('guards the chapter body against copy for a moderator', function () {
        assertCopyGuarded(viewChapterAs($this, moderator($this)));
    });

    it('guards the chapter body against copy for an admin', function () {
        assertCopyGuarded(viewChapterAs($this, admin($this)));
    });

    it('does not guard the chapter body for the author', function () {
        $author = alice($this);
        $story = publicStory('Own Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $resp = $this->actingAs($author)
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertOk();
        assertNotCopyGuarded($resp);
    });

    it('does not guard the chapter body for a co-author', function () {
        assertNotCopyGuarded(viewChapterAs($this, bob($this), fn ($story, $v) => addCollaborator($story->id, $v->id, 'author')));
    });
});
