<?php

use App\Domains\Comment\Private\Support\Moderation\CommentSnapshotFormatter;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
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

        $policyRegistry = new CommentPolicyRegistry();
        $formatter = new CommentSnapshotFormatter($policyRegistry);
        $snapshot = $formatter->capture($commentId);

        expect($snapshot)
            ->toHaveKeys(['body'])
            ->and($snapshot['body'])->toContain($text);
    });

    it('render outputs labels and see-more structure', function () {
        $this->actingAs($this->author);
        $long = generateDummyText(500) . 'TAIL';
        $commentId = createComment($this->entityType, $this->entityId, $long);

        $policyRegistry = new CommentPolicyRegistry();
        $formatter = new CommentSnapshotFormatter($policyRegistry);    
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

        $policyRegistry = new CommentPolicyRegistry();
        $formatter = new CommentSnapshotFormatter($policyRegistry);
        expect($formatter->getReportedUserId($commentId))->toBe($this->author->id);
    });

    it('getContentUrl returns "/" for unknown entity types (default policy)', function () {
        $this->actingAs($this->author);
        $commentId = createComment('unknown_type', $this->entityId, generateDummyText(150));

        $policyRegistry = new CommentPolicyRegistry();
        $formatter = new CommentSnapshotFormatter($policyRegistry);
        $url = $formatter->getContentUrl($commentId);

        expect($url)->toBe('/');
    });

    it('getContentUrl returns "/" when comment does not exist', function () {
        $policyRegistry = new CommentPolicyRegistry();
        $formatter = new CommentSnapshotFormatter($policyRegistry);
        
        $url = $formatter->getContentUrl(999999);
        expect($url)->toBe('/');
    });

    it('getContentUrl returns proper chapter URL when ChapterCommentPolicy is registered', function () {
        $this->actingAs($this->author);
        
        // Create a fake comment policy for testing
        $fakePolicy = new class implements \App\Domains\Comment\Public\Api\Contracts\CommentPolicy {
            public function validateCreate(\App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto $dto): void {}
            public function canCreateRoot(int $entityId, int $userId): bool { return true; }
            public function canReply(\App\Domains\Comment\Public\Api\Contracts\CommentDto $parentComment, int $userId): bool { return true; }
            public function canEditOwn(\App\Domains\Comment\Public\Api\Contracts\CommentDto $comment, int $userId): bool { return true; }
            public function validateEdit(\App\Domains\Comment\Public\Api\Contracts\CommentDto $comment, int $userId, string $newBody): void {}
            public function getRootCommentMinLength(): ?int { return null; }
            public function getRootCommentMaxLength(): ?int { return null; }
            public function getReplyCommentMinLength(): ?int { return null; }
            public function getReplyCommentMaxLength(): ?int { return null; }
            public function getUrl(int $entityId, int $commentId): ?string {
                return 'http://localhost/fake-entity/' . $entityId . '?comment=' . $commentId;
            }
        };
        
        $commentId = createComment('fake', 123, generateDummyText(150));

        $policyRegistry = new CommentPolicyRegistry();
        $policyRegistry->register('fake', $fakePolicy);
        
        $formatter = new CommentSnapshotFormatter($policyRegistry);
        $url = $formatter->getContentUrl($commentId);

        expect($url)->toBe('http://localhost/fake-entity/123?comment=' . $commentId);
    });
});
