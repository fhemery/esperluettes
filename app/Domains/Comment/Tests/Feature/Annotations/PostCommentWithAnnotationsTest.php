<?php

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\AnnotationToCreateDto;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Comment\Public\Events\CommentPosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Build a POST /comments payload for a chapter root comment carrying annotations,
 * serialised the way the client does it: one hidden `annotations` JSON input.
 */
function annotatedChapterCommentPayload(int $chapterId, array $annotations, array $overrides = []): array
{
    return array_merge([
        'entity_type' => 'chapter',
        'entity_id' => $chapterId,
        'body' => generateDummyText(140),
        'annotations' => json_encode($annotations),
    ], $overrides);
}

function annotationItem(array $overrides = []): array
{
    return array_merge([
        'body' => '<p>Belle phrase</p>',
        'highlighted_text' => 'le passage choisi',
        'prefix' => 'avant ',
        'suffix' => ' après',
    ], $overrides);
}

describe('POST /comments with annotations', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);
        $this->from('/chapters/whatever#comments');
    });

    it('stores the root comment and every annotation, attached to it and authored by the poster', function () {
        $this->actingAs($this->reader);

        $response = $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['highlighted_text' => 'premier']),
            annotationItem(['highlighted_text' => 'second', 'prefix' => null, 'suffix' => null]),
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $comment = Comment::query()->where('commentable_type', 'chapter')->where('commentable_id', $this->chapter->id)->sole();
        $annotations = CommentAnnotation::query()->orderBy('id')->get();

        expect($annotations)->toHaveCount(2)
            ->and($annotations->pluck('comment_id')->unique()->all())->toBe([$comment->id])
            ->and($annotations->pluck('author_id')->unique()->all())->toBe([$this->reader->id])
            ->and($annotations->pluck('parent_annotation_id')->unique()->all())->toBe([null])
            ->and($annotations[0]->highlighted_text)->toBe('premier')
            ->and($annotations[0]->prefix)->toBe('avant ')
            ->and($annotations[0]->suffix)->toBe(' après')
            ->and($annotations[1]->highlighted_text)->toBe('second')
            ->and($annotations[1]->prefix)->toBeNull()
            ->and($annotations[1]->suffix)->toBeNull()
            ->and($annotations[0]->is_processed)->toBeFalse();
    });

    it('stores a root comment without annotations exactly as before', function () {
        $this->actingAs($this->reader);

        $response = $this->post('/comments', [
            'entity_type' => 'chapter',
            'entity_id' => $this->chapter->id,
            'body' => generateDummyText(140),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('comment.draft_consumed');
        expect(Comment::query()->count())->toBe(1)
            ->and(CommentAnnotation::query()->count())->toBe(0);
    });

    it('sanitizes annotation bodies with the annotation profile', function () {
        $this->actingAs($this->reader);

        $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>Bien <strong>vu</strong> <em>ici</em><script>alert(1)</script> <a href="https://x.test">lien</a></p><ul><li>liste</li></ul>']),
        ]))->assertSessionHasNoErrors();

        $body = CommentAnnotation::query()->sole()->body;

        expect($body)->toContain('<strong>vu</strong>')
            ->and($body)->toContain('<em>ici</em>')
            ->and($body)->not->toContain('<script')
            ->and($body)->not->toContain('alert(1)')
            ->and($body)->not->toContain('<a ')
            ->and($body)->not->toContain('<ul')
            ->and($body)->not->toContain('<li');
    });

    it('rejects the whole post when one annotation body is blank (no comment row, no annotation row)', function () {
        $this->actingAs($this->reader);

        $response = $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(),
            annotationItem(['body' => '<p>   </p>']),
        ]));

        $response->assertSessionHasErrors('annotations');
        expect(Comment::query()->count())->toBe(0)
            ->and(CommentAnnotation::query()->count())->toBe(0);
    });

    it('rejects a body over 1000 plain characters and a highlight over 500', function () {
        $this->actingAs($this->reader);

        $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>' . str_repeat('a', 1001) . '</p>']),
        ]))->assertSessionHasErrors('annotations');

        $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['highlighted_text' => str_repeat('a', 501)]),
        ]))->assertSessionHasErrors();

        // The public API enforces the policy's highlight cap too, not only the form request.
        expect(fn () => app(CommentPublicApi::class)->create(new CommentToCreateDto(
            entityType: 'chapter',
            entityId: $this->chapter->id,
            body: generateDummyText(140),
            parentCommentId: null,
            annotations: [new AnnotationToCreateDto('<p>ok</p>', str_repeat('a', 501), null, null)],
        )))->toThrow(ValidationException::class);

        expect(Comment::query()->count())->toBe(0)
            ->and(CommentAnnotation::query()->count())->toBe(0);
    });

    it('rejects annotations sent with a reply', function () {
        $commenter = carol($this);
        $this->actingAs($commenter);
        $rootId = createComment('chapter', $this->chapter->id, generateDummyText(140));

        $this->actingAs($this->author);
        $response = $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [annotationItem()], [
            'parent_comment_id' => $rootId,
            'body' => 'Merci pour ce retour',
        ]));

        $response->assertSessionHasErrors('annotations');
        expect(Comment::query()->count())->toBe(1)
            ->and(CommentAnnotation::query()->count())->toBe(0);
    });

    it('rejects annotations from the chapter author, a co-author, and a reader who already has a root comment', function () {
        $coAuthor = carol($this);
        addCollaborator($this->story->id, $coAuthor->id, 'author');

        $this->actingAs($this->reader);
        createComment('chapter', $this->chapter->id, generateDummyText(140));

        foreach ([$this->author, $coAuthor, $this->reader] as $user) {
            $this->actingAs($user);
            $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [annotationItem()]))
                ->assertSessionHasErrors('annotations');
        }

        expect(Comment::query()->count())->toBe(1)
            ->and(CommentAnnotation::query()->count())->toBe(0);
    });

    it('keeps the old body and returns errors under the annotations key on rejection', function () {
        $this->actingAs($this->author);
        $body = generateDummyText(140);

        $response = $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [annotationItem()], ['body' => $body]));

        $response->assertRedirect('/chapters/whatever#comments');
        $response->assertSessionHasErrors(['annotations' => __('comment::annotations.errors.not_allowed')]);
        $response->assertSessionHasInput('body', $body);
        $response->assertSessionMissing('comment.draft_consumed');
    });

    it('fires exactly one CommentPosted for a root comment with three annotations', function () {
        $this->actingAs($this->reader);

        $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(),
            annotationItem(),
            annotationItem(),
        ]))->assertSessionHasNoErrors();

        expect(CommentAnnotation::query()->count())->toBe(3)
            ->and(countEvents(CommentPosted::name()))->toBe(1);
    });

    it('rejects an annotations input that is not valid JSON', function () {
        $this->actingAs($this->reader);

        $response = $this->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [], [
            'annotations' => '{not json',
        ]));

        $response->assertSessionHasErrors('annotations');
        expect(Comment::query()->count())->toBe(0)
            ->and(CommentAnnotation::query()->count())->toBe(0);
    });
});
