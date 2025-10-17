<?php

use App\Domains\Comment\Public\Support\Moderation\CommentSnapshotFormatter;
use App\Domains\Comment\Private\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CommentSnapshotFormatter', function () {
    beforeEach(function () {
        $this->entityType = 'chapter';
        $this->entityId = 4321;
        $this->author = alice($this);
    });

    it('capture returns expected snapshot structure and values', function () {
        $this->actingAs($this->author);
        $text = generateDummyText(150);
        $commentId = createComment($this->entityType, $this->entityId, $text);

        $formatter = new CommentSnapshotFormatter();
        $snapshot = $formatter->capture($commentId);

        expect($snapshot)
            ->toHaveKeys(['body'])
            ->and($snapshot['body'])->toContain($text);
    });

    it('render outputs labels and see-more structure', function () {
        $this->actingAs($this->author);
        $long = generateDummyText(500) . 'TAIL';
        $commentId = createComment($this->entityType, $this->entityId, $long);

        $formatter = new CommentSnapshotFormatter();    
        $snapshot = $formatter->capture($commentId);
        $html = $formatter->render($snapshot);

        expect($html)
            ->toContain(__('comment::moderation.comment_body'))
            ->toContain(__('comment::moderation.see_more'))
            ->toContain(__('comment::moderation.see_less'))
            // contains head and tail which implies full content present in DOM when expanded
            ->toContain(substr($snapshot['body'], 0, 50))
            ->toContain('TAIL');
    });

    it('getReportedUserId returns author_id', function () {
        $this->actingAs($this->author);
        $commentId = createComment($this->entityType, $this->entityId, generateDummyText(150));

        $formatter = new CommentSnapshotFormatter();
        expect($formatter->getReportedUserId($commentId))->toBe($this->author->id);
    });

    it('getContentUrl returns the comments fragment route for the target', function () {
        $this->actingAs($this->author);
        $commentId = createComment($this->entityType, $this->entityId, generateDummyText(150));
        /** @var Comment $comment */
        $comment = Comment::query()->findOrFail($commentId);

        $formatter = new CommentSnapshotFormatter();
        $url = $formatter->getContentUrl($commentId);

        expect($url)->toBe(route('comments.fragments', ['entity_type' => $comment->commentable_type, 'entity_id' => $comment->commentable_id]));
    });
});
