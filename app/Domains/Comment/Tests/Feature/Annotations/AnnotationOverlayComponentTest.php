<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** URL of the built overlay entry, as `@vite` prints it in the script tag. */
function annotationOverlayScriptUrl(): string
{
    return Vite::asset('app/Domains/Comment/Resources/js/annotations/in-chapter/index.js');
}

function renderAnnotationOverlay(TestCase $t, string $entityType, int $entityId, ?int $rootCommentId = null, string $mode = 'draft')
{
    return ($t->renderOverlay)($entityType, $entityId, $rootCommentId, $mode);
}

describe('<x-comment::annotation-overlay>', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author);
        Auth::logout();

        // blade() is protected: call it from a closure bound to the test case.
        $this->renderOverlay = fn (string $type, int $id, ?int $root, string $mode) => $this->blade(
            '<x-comment::annotation-overlay :entity-type="$type" :entity-id="$id" :root-comment-id="$root" :annotation-mode="$mode"><p data-overlay-slot>texte</p></x-comment::annotation-overlay>@stack(\'head-scripts\')',
            ['type' => $type, 'id' => $id, 'root' => $root, 'mode' => $mode]
        );
    });

    it('renders the author overlay with gutter and script for the author', function () {
        $this->actingAs($this->author);

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('data-annotation-overlay', false)
            ->assertSee('data-role="author"', false)
            ->assertSee('x-ref="gutter"', false)
            ->assertSee(annotationOverlayScriptUrl(), false)
            ->assertSee('data-entity-type="chapter"', false)
            ->assertSee('data-entity-id="' . $this->chapter->id . '"', false)
            ->assertSee('data-user-id="' . $this->author->id . '"', false)
            ->assertSee('data-url="' . e(route('comments.annotations.in-entity')) . '"', false)
            ->assertSeeInOrder(['data-annotation-overlay', 'data-overlay-slot'], false);
    });

    it('renders the gutter avatar buttons, hidden while the heat map is on', function () {
        $this->actingAs($this->author);

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('x-show="!$store.annotationOverlay.heatOn"', false)
            ->assertSee('<template x-for="group in groups" :key="group.key">', false)
            ->assertSee('<button type="button"', false)
            ->assertSee(':aria-label="group.label"', false)
            ->assertSee('annotation-overlay:open', false)
            ->assertSee('data-default-avatar="' . e(asset('images/default-avatar.svg')) . '"', false)
            ->assertSee('data-avatar-labels=', false)
            ->assertSee('comment::annotations.overlay.avatar_label', false)
            ->assertSee('data-more-label="comment::annotations.overlay.more"', false);
    });

    it('renders the popover with its own reply editor', function () {
        $this->actingAs($this->author);

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('x-data="annotationPopover"', false)
            ->assertSee('x-teleport="body"', false)
            ->assertSee('data-annotation-popover-panel', false)
            ->assertSee('id="annotation-overlay-reply-editor"', false)
            ->assertDontSee('id="annotation-reply-editor"', false)
            ->assertSee('x-html="current.body"', false)
            ->assertSee('comment::annotations.overlay.position', false)
            ->assertSee('comment::annotations.replies.author_hint', false)
            ->assertSee('data-reply-max-length="1000"', false);
    });

    it('renders the compact report button bound to current and reply can_report', function () {
        $this->actingAs(bob($this));

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('current.can_report ? [current.id] : []', false)
            ->assertSee("reportButton('annotation', reportId)", false)
            ->assertSee('x-if="reply.can_report"', false)
            ->assertSee("reportButton('annotation', reply.id)", false)
            ->assertSee('data-testid="annotation-popover-report"', false)
            ->assertSee('|| current.can_report', false)
            ->assertSee('aria-label="moderation::report.button"', false)
            ->assertSee('title="moderation::report.button"', false);
    });

    it('renders the filter menu for the author, at md+ above the article', function () {
        $this->actingAs($this->author);

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('x-data="annotationFilterMenu"', false)
            ->assertSee('x-show="shown"', false)
            ->assertSee('data-unknown-name="comment::comments.unknown_user"', false)
            ->assertSee('comment::annotations.filter.title', false)
            ->assertSee('comment::annotations.filter.commenters', false)
            ->assertSee('comment::annotations.show_processed', false)
            ->assertSeeInOrder(['x-data="annotationFilterMenu"', 'data-overlay-slot'], false);
    });

    it('dims the gutter avatar of a processed annotation', function () {
        $this->actingAs($this->author);

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee("{ 'opacity-50': group.processed }", false);
    });

    it('renders no filter menu for a commenter', function () {
        $this->actingAs(bob($this));

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('data-role="commenter"', false)
            ->assertDontSee('annotationFilterMenu', false)
            ->assertDontSee('comment::annotations.show_processed', false);
    });

    it('carries the viewer profile for their unsaved annotations', function () {
        $this->actingAs(bob($this));

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('data-user-name="Bob"', false)
            ->assertSee('data-user-avatar=', false);
    });

    it('renders the author overlay for a co-author', function () {
        $coAuthor = bob($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');
        $this->actingAs($coAuthor);

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('data-role="author"', false)
            ->assertSee('x-ref="gutter"', false);
    });

    it('renders the commenter overlay for every logged-in non-author', function () {
        $beta = daniel($this);
        addCollaborator($this->story->id, $beta->id, 'beta-reader');
        $viewers = [
            bob($this),
            carol($this, roles: [Roles::USER]),
            $beta,
            moderator($this),
        ];

        foreach ($viewers as $viewer) {
            $this->actingAs($viewer);

            renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
                ->assertSee('data-annotation-overlay', false)
                ->assertSee('data-role="commenter"', false)
                ->assertSee('data-user-id="' . $viewer->id . '"', false);
        }
    });

    it('carries the annotation mode and root comment id', function () {
        $this->actingAs(bob($this));

        renderAnnotationOverlay($this, 'chapter', $this->chapter->id, 42, 'pending')
            ->assertSee('data-mode="pending"', false)
            ->assertSee('data-root-comment-id="42"', false);
    });

    it('renders only its slot for a guest', function () {
        renderAnnotationOverlay($this, 'chapter', $this->chapter->id)
            ->assertSee('data-overlay-slot', false)
            ->assertDontSee('data-annotation-overlay', false)
            ->assertDontSee('x-ref="gutter"', false)
            ->assertDontSee(annotationOverlayScriptUrl(), false);
    });

    it('renders only its slot for an entity type without annotations', function () {
        $this->actingAs($this->author);

        renderAnnotationOverlay($this, 'news', $this->chapter->id)
            ->assertSee('data-overlay-slot', false)
            ->assertDontSee('data-annotation-overlay', false)
            ->assertDontSee(annotationOverlayScriptUrl(), false);
    });
});
