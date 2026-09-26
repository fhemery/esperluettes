<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('« Citer » toolbar button — quotable area declaration', function () {
    it('declares the quotable area on the Citer button for a confirmed reader', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertOk()
            ->assertSee('quote-toolbar-btn', false)
            ->assertSee('data-requires-selection-within=".ce-block--text"', false);
    });

    it('renders no Citer button for a guest', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        Auth::logout();

        $this->get(route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]))
            ->assertOk()
            ->assertDontSee('quote-toolbar-btn', false)
            ->assertDontSee('data-requires-selection-within', false);
    });
});
