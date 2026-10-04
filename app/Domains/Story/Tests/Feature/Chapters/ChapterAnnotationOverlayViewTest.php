<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Annotation overlay on the chapter page', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author);
        $this->url = route('chapters.show', ['storySlug' => $this->story->slug, 'chapterSlug' => $this->chapter->slug]);
    });

    it('wraps the article in the overlay, inside the author heat wrapper, for the author', function () {
        $this->actingAs($this->author)
            ->get($this->url)
            ->assertOk()
            ->assertSee('data-quote-author-heat', false)
            ->assertSeeInOrder(['data-quote-author-heat', 'data-annotation-overlay', 'data-role="author"', 'data-quote-article'], false)
            // Loaded before app.js, so its alpine:init listener is registered before Alpine.start().
            ->assertSeeInOrder([
                Vite::asset('app/Domains/Comment/Resources/js/annotations/in-chapter/index.js'),
                Vite::asset('app/Domains/Shared/Resources/js/app.js'),
            ], false);
    });

    it('wraps the article in the commenter overlay for a reader', function () {
        $this->actingAs(bob($this))
            ->get($this->url)
            ->assertOk()
            ->assertSeeInOrder(['data-annotation-overlay', 'data-role="commenter"', 'data-quote-article'], false);
    });

    it('renders no overlay for a guest', function () {
        Auth::logout();

        $this->get($this->url)
            ->assertOk()
            ->assertSee('data-quote-article', false)
            ->assertDontSee('data-annotation-overlay', false)
            ->assertDontSee(Vite::asset('app/Domains/Comment/Resources/js/annotations/in-chapter/index.js'), false);
    });
});
