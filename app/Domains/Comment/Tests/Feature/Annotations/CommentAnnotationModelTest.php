<?php

declare(strict_types=1);

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Models\CommentAnnotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAnnotatedComment(): Comment
{
    return Comment::create([
        'commentable_type' => 'default',
        'commentable_id' => 1001,
        'author_id' => 1,
        'body' => '<p>Root comment</p>',
    ]);
}

function makeRootAnnotation(Comment $comment, array $overrides = []): CommentAnnotation
{
    return CommentAnnotation::create(array_merge([
        'comment_id' => $comment->id,
        'author_id' => 1,
        'body' => '<p>Nice sentence</p>',
        'highlighted_text' => 'the quick brown fox',
        'prefix' => 'Once upon a time',
        'suffix' => 'jumped over',
    ], $overrides));
}

describe('CommentAnnotation model', function () {
    it('persists a root annotation under a comment and reads it back with casts', function () {
        $comment = makeAnnotatedComment();
        $created = makeRootAnnotation($comment, [
            'is_processed' => true,
            'processed_at' => now(),
        ]);

        $annotation = CommentAnnotation::findOrFail($created->id);

        expect($annotation->comment_id)->toBe($comment->id)
            ->and($annotation->author_id)->toBe(1)
            ->and($annotation->parent_annotation_id)->toBeNull()
            ->and($annotation->body)->toBe('<p>Nice sentence</p>')
            ->and($annotation->highlighted_text)->toBe('the quick brown fox')
            ->and($annotation->prefix)->toBe('Once upon a time')
            ->and($annotation->suffix)->toBe('jumped over')
            ->and($annotation->is_processed)->toBeTrue()
            ->and($annotation->processed_at)->toBeInstanceOf(\Carbon\CarbonInterface::class)
            ->and($annotation->comment->id)->toBe($comment->id);
    });

    it('defaults is_processed to false', function () {
        $annotation = makeRootAnnotation(makeAnnotatedComment());

        expect($annotation->fresh()->is_processed)->toBeFalse()
            ->and($annotation->fresh()->processed_at)->toBeNull();
    });

    it('soft-deletes and restores an annotation', function () {
        $annotation = makeRootAnnotation(makeAnnotatedComment());

        $annotation->delete();
        expect(CommentAnnotation::find($annotation->id))->toBeNull()
            ->and(CommentAnnotation::withTrashed()->find($annotation->id))->not->toBeNull();

        $annotation->restore();
        expect(CommentAnnotation::find($annotation->id))->not->toBeNull();
    });

    it('removes annotations when their comment is force-deleted', function () {
        $comment = makeAnnotatedComment();
        $root = makeRootAnnotation($comment);
        CommentAnnotation::create([
            'comment_id' => $comment->id,
            'parent_annotation_id' => $root->id,
            'author_id' => 2,
            'body' => '<p>Reply</p>',
        ]);
        $trashed = makeRootAnnotation($comment);
        $trashed->delete();

        $comment->forceDelete();

        expect(DB::table('comment_annotations')->where('comment_id', $comment->id)->count())->toBe(0);
    });

    it('scopes roots and replies apart', function () {
        $comment = makeAnnotatedComment();
        $root = makeRootAnnotation($comment);
        $reply = CommentAnnotation::create([
            'comment_id' => $comment->id,
            'parent_annotation_id' => $root->id,
            'author_id' => 2,
            'body' => '<p>Reply</p>',
        ]);

        expect(CommentAnnotation::roots()->pluck('id')->all())->toBe([$root->id])
            ->and(CommentAnnotation::repliesOnly()->pluck('id')->all())->toBe([$reply->id])
            ->and($root->replies()->pluck('id')->all())->toBe([$reply->id])
            ->and($reply->parent->id)->toBe($root->id);
    });
});
