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

    it('renders the passage popover only for the author', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($author)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertSee('quoteAuthorPassagePanel(', false);

        $this->actingAs($reader)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('quoteAuthorPassagePanel(', false);
    });

    it('renders the chapter summary, with the stale wording, only for the author', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($author)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertSee('data-quote-author-summary', false)
            ->assertSee(__('quote::ui.author_summary.title'), false)
            ->assertSee(__('quote::ui.profile_tab.passage_missing'), false);

        $this->actingAs($reader)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-quote-author-summary', false);
    });

    it('keeps the stale wording out of the clamped passage text', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        createQuote($reader->id, $chapter->id, $story->id);

        $html = $this->actingAs($author)
            ->get(chapterUrl($story, $chapter))
            ->assertOk()
            ->getContent();

        $wording = __('quote::ui.profile_tab.passage_missing');
        expect($html)->toContain($wording);

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // The explanation must never sit inside a truncating element, or the
        // clamp cuts away the very words that explain the untinted passage.
        $clamped = (new DOMXPath($dom))->query('//*[contains(@class, "line-clamp")]');
        expect($clamped->length)->toBeGreaterThan(0);

        foreach ($clamped as $node) {
            expect($node->textContent)->not->toContain($wording);
        }
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
