<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function saveAnnotationAdd(string $key, array $overrides = []): array
{
    return array_merge([
        'key' => $key,
        'body' => '<p>❤️</p>',
        'highlighted_text' => 'nouveau passage',
        // No edge spaces: JSON bodies go through TrimStrings (re-anchoring compares words, not spaces).
        'prefix' => 'juste avant',
        'suffix' => 'juste après',
    ], $overrides);
}

/**
 * Snapshot of every annotation row (trashed included), to prove a refused save wrote nothing.
 */
function annotationRowsSnapshot(): array
{
    return CommentAnnotation::withTrashed()->orderBy('id')->get()
        ->map(fn (CommentAnnotation $a) => [
            'id' => $a->id,
            'body' => $a->body,
            'is_processed' => $a->is_processed,
            'deleted_at' => $a->deleted_at?->toISOString(),
            'parent_annotation_id' => $a->parent_annotation_id,
        ])
        ->all();
}

describe('PUT /comments/{commentId}/annotations', function () {
    beforeEach(function () {
        $this->author = alice($this);
        $this->story = publicStory('Public Story', $this->author->id);
        $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Pub Chap']);
        $this->reader = bob($this);

        $this->actingAs($this->reader);
        $this->from('/chapters/whatever#comments')->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>Premier avis</p>', 'highlighted_text' => 'premier passage']),
            annotationItem(['body' => '<p>Second avis</p>', 'highlighted_text' => 'second passage']),
        ]))->assertSessionHasNoErrors();
        auth()->logout();

        $this->comment = Comment::query()->sole();
        [$this->first, $this->second] = CommentAnnotation::query()->orderBy('id')->get()->all();
        $this->url = '/comments/' . $this->comment->id . '/annotations';
    });

    it('applies adds, edits and deletes in one call and returns the refreshed list', function () {
        $response = $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [saveAnnotationAdd('tmp-1')],
            'edits' => [['id' => $this->first->id, 'body' => '<p>Avis revu</p>']],
            'deletes' => [$this->second->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('comment_id', $this->comment->id);
        $response->assertJsonPath('viewer_role', 'commenter');
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('items.0.id', $this->first->id);
        expect($response->json('items.0.body'))->toContain('Avis revu')
            ->and($response->json('items.1.highlighted_text'))->toBe('nouveau passage')
            ->and($response->json('items.1.author_id'))->toBe($this->reader->id);

        expect(CommentAnnotation::query()->find($this->second->id))->toBeNull()
            ->and(CommentAnnotation::withTrashed()->find($this->second->id)->trashed())->toBeTrue();
        $added = CommentAnnotation::query()->orderByDesc('id')->first();
        expect($added->comment_id)->toBe($this->comment->id)
            ->and($added->author_id)->toBe($this->reader->id)
            ->and($added->parent_annotation_id)->toBeNull()
            ->and($added->prefix)->toBe('juste avant')
            ->and($added->suffix)->toBe('juste après');
    });

    it('accepts an empty change set as a no-op', function () {
        $before = annotationRowsSnapshot();

        $this->actingAs($this->reader)->putJson($this->url, [])
            ->assertOk()
            ->assertJsonCount(2, 'items');

        expect(annotationRowsSnapshot())->toBe($before);
    });

    it('resets the processed flag when the commenter edits a processed annotation', function () {
        $this->first->update(['is_processed' => true, 'processed_at' => now()]);

        $this->actingAs($this->reader)->putJson($this->url, [
            'edits' => [['id' => $this->first->id, 'body' => '<p>Avis revu</p>']],
        ])->assertOk();

        $fresh = $this->first->fresh();
        expect($fresh->is_processed)->toBeFalse()
            ->and($fresh->processed_at)->toBeNull();
    });

    it('leaves highlight, prefix and suffix unchanged on edit', function () {
        $this->actingAs($this->reader)->putJson($this->url, [
            'edits' => [['id' => $this->first->id, 'body' => '<p>Avis revu</p>']],
        ])->assertOk();

        $fresh = $this->first->fresh();
        expect($fresh->highlighted_text)->toBe('premier passage')
            ->and($fresh->prefix)->toBe('avant ')
            ->and($fresh->suffix)->toBe(' après')
            ->and($fresh->body)->toContain('Avis revu');
    });

    it('sanitizes the edited body', function () {
        $this->actingAs($this->reader)->putJson($this->url, [
            'edits' => [['id' => $this->first->id, 'body' => '<p>Revu<script>alert(1)</script></p>']],
        ])->assertOk();

        expect($this->first->fresh()->body)->not->toContain('<script');
    });

    it('soft-deletes the replies of a deleted annotation', function () {
        $reply = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => $this->second->id,
            'author_id' => $this->author->id,
            'body' => '<p>Merci</p>',
            'highlighted_text' => 'second passage',
        ]);

        $this->actingAs($this->reader)->putJson($this->url, [
            'deletes' => [$this->second->id],
        ])->assertOk();

        expect(CommentAnnotation::withTrashed()->find($reply->id)->trashed())->toBeTrue()
            ->and($this->first->fresh()->trashed())->toBeFalse();
    });

    it('writes nothing when one delete id is stale and names it under deletes.<id>', function () {
        $this->second->delete();
        $before = annotationRowsSnapshot();
        $staleId = $this->second->id;

        $response = $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [saveAnnotationAdd('tmp-1')],
            'edits' => [['id' => $this->first->id, 'body' => '<p>Avis revu</p>']],
            'deletes' => [$staleId, 999999],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'deletes.' . $staleId => __('comment::annotations.errors.stale'),
            'deletes.999999' => __('comment::annotations.errors.stale'),
        ]);
        $response->assertJsonMissingValidationErrors(['edits.' . $this->first->id, 'adds.tmp-1']);
        expect(annotationRowsSnapshot())->toBe($before);
    });

    it('writes nothing when one add is invalid and names it under adds.<key>', function () {
        $before = annotationRowsSnapshot();

        $response = $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [
                saveAnnotationAdd('ok-1'),
                saveAnnotationAdd('too-long', ['highlighted_text' => str_repeat('a', 501)]),
                saveAnnotationAdd('blank', ['body' => '<p> </p>']),
            ],
            'edits' => [['id' => $this->first->id, 'body' => '<p>' . str_repeat('b', 1001) . '</p>']],
            'deletes' => [$this->second->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['adds.too-long', 'adds.blank', 'edits.' . $this->first->id]);
        $response->assertJsonMissingValidationErrors(['adds.ok-1', 'deletes.' . $this->second->id]);
        expect(annotationRowsSnapshot())->toBe($before);
    });

    it('names an id both edited and deleted as a stale edit', function () {
        $before = annotationRowsSnapshot();

        $response = $this->actingAs($this->reader)->putJson($this->url, [
            'edits' => [['id' => $this->first->id, 'body' => '<p>Avis revu</p>']],
            'deletes' => [$this->first->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['edits.' . $this->first->id => __('comment::annotations.errors.stale')]);
        expect(annotationRowsSnapshot())->toBe($before);
    });

    it('refuses an edit of another user’s annotation, of a reply, and of an annotation under another comment as edits.<id>', function () {
        // Another reader's root comment + annotation on the same chapter.
        $other = carol($this);
        $this->actingAs($other);
        $this->from('/chapters/whatever#comments')->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(['body' => '<p>Avis de Carol</p>']),
        ]))->assertSessionHasNoErrors();
        $foreign = CommentAnnotation::query()->where('author_id', $other->id)->sole();

        // A reply under the reader's annotation, written by the reader (same comment).
        $reply = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => $this->first->id,
            'author_id' => $this->reader->id,
            'body' => '<p>Réponse</p>',
            'highlighted_text' => 'premier passage',
        ]);

        // An annotation under this comment but written by someone else.
        $squatter = CommentAnnotation::query()->create([
            'comment_id' => $this->comment->id,
            'parent_annotation_id' => null,
            'author_id' => $other->id,
            'body' => '<p>Intrus</p>',
            'highlighted_text' => 'premier passage',
        ]);
        $before = annotationRowsSnapshot();

        $response = $this->actingAs($this->reader)->putJson($this->url, [
            'edits' => [
                ['id' => $foreign->id, 'body' => '<p>Piraté</p>'],
                ['id' => $reply->id, 'body' => '<p>Piraté</p>'],
                ['id' => $squatter->id, 'body' => '<p>Piraté</p>'],
            ],
            'deletes' => [$foreign->id + 1000],
        ]);

        $response->assertUnprocessable();
        $stale = __('comment::annotations.errors.stale');
        $response->assertJsonValidationErrors([
            'edits.' . $foreign->id => $stale,
            'edits.' . $reply->id => $stale,
            'edits.' . $squatter->id => $stale,
        ]);
        expect(annotationRowsSnapshot())->toBe($before)
            ->and($response->getContent())->not->toContain('Avis de Carol');
    });

    it('refuses a delete of another user’s annotation as deletes.<id>', function () {
        $other = carol($this);
        $this->actingAs($other);
        $this->from('/chapters/whatever#comments')->post('/comments', annotatedChapterCommentPayload($this->chapter->id, [
            annotationItem(),
        ]))->assertSessionHasNoErrors();
        $foreign = CommentAnnotation::query()->where('author_id', $other->id)->sole();

        $this->actingAs($this->reader)->putJson($this->url, ['deletes' => [$foreign->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deletes.' . $foreign->id => __('comment::annotations.errors.stale')]);

        expect($foreign->fresh()->trashed())->toBeFalse();
    });

    it('returns 403 when the user is not the root comment’s author', function () {
        $before = annotationRowsSnapshot();
        $payload = [
            'adds' => [saveAnnotationAdd('tmp-1')],
            'edits' => [['id' => $this->first->id, 'body' => '<p>Piraté</p>']],
            'deletes' => [$this->second->id],
        ];

        foreach ([carol($this), $this->author, moderator($this)] as $user) {
            $response = $this->actingAs($user)->putJson($this->url, $payload);
            $response->assertForbidden();
            expect($response->getContent())->not->toContain('Premier avis');
        }

        expect(annotationRowsSnapshot())->toBe($before);
    });

    it('returns 403 for a reply comment id', function () {
        $this->actingAs($this->author);
        $replyId = createComment('chapter', $this->chapter->id, generateDummyText(20), $this->comment->id);

        // The reply's own author is refused too: annotations live under root comments only.
        $this->actingAs($this->author)->putJson('/comments/' . $replyId . '/annotations', [
            'adds' => [saveAnnotationAdd('tmp-1')],
        ])->assertForbidden();

        expect(CommentAnnotation::query()->where('comment_id', $replyId)->count())->toBe(0);
    });

    it('returns 404 for a trashed root comment', function () {
        $this->comment->delete();

        $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [saveAnnotationAdd('tmp-1')],
        ])->assertNotFound();

        expect(CommentAnnotation::query()->count())->toBe(2);
    });

    it('works after a moderator emptied the root comment', function () {
        $this->actingAs(moderator($this))
            ->post(route('comments.moderation.empty-content', ['commentId' => $this->comment->id]))
            ->assertRedirect();

        $response = $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [saveAnnotationAdd('tmp-1')],
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.highlighted_text', 'nouveau passage');
    });

    it('returns 403 on a news comment', function () {
        $news = News::factory()->published()->create(['created_by' => admin($this)->id]);
        $commenter = carol($this);
        $this->actingAs($commenter);
        $rootId = createComment('news', $news->id, generateDummyText(20), null);

        $this->actingAs($commenter)->putJson('/comments/' . $rootId . '/annotations', [
            'adds' => [saveAnnotationAdd('tmp-1')],
        ])->assertForbidden();

        expect(CommentAnnotation::query()->where('comment_id', $rootId)->count())->toBe(0);
    });

    it('redirects a guest to login', function () {
        $this->put($this->url, ['deletes' => [$this->first->id]])->assertRedirect(route('login'));

        expect($this->first->fresh()->trashed())->toBeFalse();
    });

    it('lets a non-confirmed user save like a confirmed one', function () {
        $unconfirmed = carol($this, roles: [Roles::USER]);
        $root = Comment::query()->create([
            'commentable_type' => 'chapter',
            'commentable_id' => $this->chapter->id,
            'author_id' => $unconfirmed->id,
            'body' => generateDummyText(140),
            'parent_comment_id' => null,
        ]);

        $this->actingAs($unconfirmed)->putJson('/comments/' . $root->id . '/annotations', [
            'adds' => [saveAnnotationAdd('tmp-1')],
        ])->assertOk()->assertJsonCount(1, 'items');

        expect(CommentAnnotation::query()->where('comment_id', $root->id)->count())->toBe(1);
    });

    it('rejects a malformed payload positionally', function () {
        $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [saveAnnotationAdd('dup'), saveAnnotationAdd('dup')],
            'edits' => [['id' => 'abc', 'body' => '<p>x</p>']],
            'deletes' => ['x'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['adds.0.key', 'edits.0.id', 'deletes.0']);
    });

    it('dispatches no domain event', function () {
        $before = count(app(EventPublicApi::class)->list());

        $this->actingAs($this->reader)->putJson($this->url, [
            'adds' => [saveAnnotationAdd('tmp-1')],
            'edits' => [['id' => $this->first->id, 'body' => '<p>Avis revu</p>']],
            'deletes' => [$this->second->id],
        ])->assertOk();

        expect(count(app(EventPublicApi::class)->list()))->toBe($before);
    });
});
