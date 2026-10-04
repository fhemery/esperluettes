<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function chapterShowUrl($story, $chapter): string
{
    return route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]);
}

describe('« Annoter » toolbar button and capture form on the chapter page', function () {
    it('a reader who has not commented sees « Annoter » with data-requires-selection-within=".ce-block--text"', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSee('class="annotation-toolbar-btn', false)
            ->assertSee(__('comment::annotations.toolbar_button.label'))
            ->assertSeeInOrder(['annotation-toolbar-btn', 'data-requires-selection-within=".ce-block--text"', 'data-requires-single-area'], false)
            ->assertSee('data-annotation-form', false)
            ->assertSee('data-user-id="' . $reader->id . '"', false)
            ->assertSee('data-entity-id="' . $chapter->id . '"', false);
    });

    it('renders data-annotation-mode="draft" and no root id for a reader without a root comment', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSeeInOrder(['data-annotable', 'data-annotation-mode="draft"'], false)
            ->assertSeeInOrder(['data-annotation-form', 'data-annotation-mode="draft"'], false)
            ->assertDontSee('data-root-comment-id', false);
    });

    it('renders data-annotation-mode="pending" and the root comment id for a reader with one', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader);
        $commentId = createComment('chapter', $chapter->id, generateDummyText(150));

        $this->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSeeInOrder(['data-annotable', 'data-annotation-mode="pending"', 'data-root-comment-id="' . $commentId . '"'], false)
            ->assertDontSee('data-annotation-mode="draft"', false);
    });

    it('shows « Annoter » and the three reaction buttons to a reader with a root comment', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader);
        createComment('chapter', $chapter->id, generateDummyText(150));

        $this->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSee('quote-toolbar-btn', false)
            ->assertSeeInOrder(['annotation-toolbar-btn', 'data-annotation-reactions'], false)
            ->assertSee('data-annotation-form', false)
            ->assertSeeInOrder(['data-annotation-form', 'data-annotation-mode="pending"'], false);
    });

    it('shows the reaction buttons with accessible labels to a reader without a root comment', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSee('data-annotation-reactions', false)
            ->assertSeeInOrder([
                'data-annotation-reactions',
                'aria-label="' . __('comment::annotations.reactions.heart') . '"', '❤️',
                'aria-label="' . __('comment::annotations.reactions.fire') . '"', '🔥',
                'aria-label="' . __('comment::annotations.reactions.thumbs_up') . '"', '👍',
            ], false)
            ->assertSeeInOrder(['data-annotation-reactions', 'data-requires-selection-within=".ce-block--text"', 'data-requires-single-area'], false)
            ->assertSee('data-user-id="' . $reader->id . '"', false);
    });

    it('shows no reaction buttons to the author, a guest, or on a page where canAnnotate is false', function () {
        $author = alice($this);
        $coAuthor = carol($this);
        $story = publicStory('Story', $author->id);
        addCollaborator($story->id, $coAuthor->id, 'author');
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($author)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-annotation-reactions', false);

        // canAnnotate is false for a co-author: the page renders no reaction.
        $this->actingAs($coAuthor)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSee('data-can-annotate="false"', false)
            ->assertDontSee('data-annotation-reactions', false);

        Auth::logout();
        $this->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('data-annotation-reactions', false);
    });

    it('the author and a co-author see neither the button nor the form', function () {
        $author = alice($this);
        $coAuthor = carol($this);
        $story = publicStory('Story', $author->id);
        addCollaborator($story->id, $coAuthor->id, 'author');
        $chapter = createPublishedChapter($this, $story, $author);

        foreach ([$author, $coAuthor] as $user) {
            $this->actingAs($user)
                ->get(chapterShowUrl($story, $chapter))
                ->assertOk()
                ->assertDontSee('annotation-toolbar-btn', false)
                ->assertDontSee('data-annotation-form', false)
                ->assertSee('data-can-annotate="false"', false);
        }
    });

    it('a guest sees no toolbar action and no form', function () {
        $author = alice($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        Auth::logout();

        $this->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertDontSee('annotation-toolbar-btn', false)
            ->assertDontSee('quote-toolbar-btn', false)
            ->assertDontSee('data-annotation-form', false)
            ->assertSee('data-can-annotate="false"', false);
    });

    it('a non-confirmed reader (role user) who may comment sees « Annoter » and gets data-can-annotate="true" without « Citer »', function () {
        $author = alice($this);
        $reader = bob($this, roles: [Roles::USER]);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSee('annotation-toolbar-btn', false)
            ->assertSee('data-can-annotate="true"', false)
            ->assertDontSee('quote-toolbar-btn', false);
    });

    it('the annotation form renders the inline toolbar', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)
            ->get(chapterShowUrl($story, $chapter))
            ->assertOk()
            ->assertSee('id="annotation-body-editor"', false)
            ->assertSee('data-toolbar="' . e(json_encode(['bold', 'italic', 'custom-emoji'])) . '"', false)
            ->assertSee('data-max="1000"', false);
    });
});
