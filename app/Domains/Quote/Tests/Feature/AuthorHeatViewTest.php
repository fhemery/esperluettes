<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function chapterUrl($story, $chapter): string
{
    return route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]);
}

describe('author view — badge and heat root on the chapter page', function () {
    it('renders the citations badge with the chapter count for the author', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($author)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertSee('data-quote-author-badge="2"', false)
            ->assertSee('data-quote-author-heat', false);
    });

    it('renders 0 citation on a chapter with no quotes', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($author)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertSee('data-quote-author-badge="0"', false);
    });

    it('renders the badge for a co-author', function () {
        $author = alice($this);
        $coAuthor = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $coAuthor->id, 'author');

        $this->actingAs($coAuthor)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertSee('data-quote-author-badge', false);
    });

    it('renders no badge and no heat root for a confirmed reader', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($reader)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-quote-author-badge', false)
            ->assertDontSee('data-quote-author-heat', false);
    });

    it('renders no badge and no heat root for a beta reader', function () {
        $author = alice($this);
        $betaReader = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        addCollaborator($story->id, $betaReader->id, 'beta-reader');

        $this->actingAs($betaReader)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-quote-author-badge', false)
            ->assertDontSee('data-quote-author-heat', false);
    });

    it('renders no badge and no heat root for a moderator', function () {
        $author = alice($this);
        $moderator = moderator($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($moderator)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-quote-author-badge', false)
            ->assertDontSee('data-quote-author-heat', false);
    });

    it('renders no badge and no heat root for an admin', function () {
        $author = alice($this);
        $admin = admin($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($admin)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-quote-author-badge', false)
            ->assertDontSee('data-quote-author-heat', false);
    });

    it('renders no badge and no heat root for a guest', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        Auth::logout();

        $this->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-quote-author-badge', false)
            ->assertDontSee('data-quote-author-heat', false);
    });
});
