<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Comment Deep Linking', function () {
    beforeEach(function () {
        $this->user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($this->user);
    });

    describe('Root Comment Deep Linking', function () {
        it('should pre-load pages when linking to a root comment on page 3', function () {
            // Create 15 comments (3 pages with perPage=5)
            $commentIds = createSeveralComments(15, 'story', 123, 'Root comment');
            
            // Target the oldest comment (should be on page 3 due to DESC ordering)
            $targetCommentId = $commentIds[0]; // "Root comment 0" - oldest, should be on page 3
            
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-' . $targetCommentId,
            ]);

            // Should contain all 15 comments (pages 1-3 pre-loaded)
            expect($html)->toContain('Root comment 0');
            expect($html)->toContain('Root comment 14');
            
            // Should have the target comment with proper id
            expect($html)->toContain('id="comment-' . $targetCommentId . '"');
            
            // Should not contain "load more" since all pages are loaded
            expect($html)->not()->toContain('Load more');
        });

        it('should load only page 1 when linking to a root comment on page 1', function () {
            // Create 3 comments (all on page 1 with perPage=5)
            $commentIds = createSeveralComments(3, 'story', 123, 'Root comment');
            
            // Target first comment
            $targetCommentId = $commentIds[0];
            
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-' . $targetCommentId,
            ]);

            // Should contain all 3 comments
            expect($html)->toContain('Root comment 0');
            expect($html)->toContain('Root comment 2');
            
            // Should have the target comment with proper id
            expect($html)->toContain('id="comment-' . $targetCommentId . '"');
        });
    });

    describe('Reply Comment Deep Linking', function () {
        it('should load parent root comment when linking to a reply', function () {
            // Create a root comment
            $rootCommentId = createComment('story', 123, 'Parent root comment');
            
            // Create replies to the root comment
            $replyId1 = createComment('story', 123, 'Reply 1', $rootCommentId);
            $replyId2 = createComment('story', 123, 'Reply 2', $rootCommentId);
            $replyId3 = createComment('story', 123, 'Reply 3', $rootCommentId);
            
            // Create more root comments to push the target to later pages
            createSeveralComments(10, 'story', 123, 'Other root comment');
            
            // Target the second reply
            $targetReplyId = $replyId2;
            
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-' . $targetReplyId,
            ]);

            // Should contain the parent root comment
            expect($html)->toContain('Parent root comment');
            
            // Should contain all replies
            expect($html)->toContain('Reply 1');
            expect($html)->toContain('Reply 2');
            expect($html)->toContain('Reply 3');
            
            // Should have the target reply with proper id
            expect($html)->toContain('id="comment-' . $targetReplyId . '"');
            
            // Should have the root comment with proper id
            expect($html)->toContain('id="comment-' . $rootCommentId . '"');
        });

        it('should load correct page when reply is on page 2', function () {
            // Create 6 root comments (page 1 will have 5 newest, page 2 will have 1 oldest)
            $rootCommentIds = createSeveralComments(6, 'story', 123, 'Root comment');
            
            // Target oldest root comment (should be on page 2 due to DESC ordering)
            $targetRootId = $rootCommentIds[0]; // "Root comment 0" - oldest, should be on page 2
            
            // Add replies to the target
            $replyId = createComment('story', 123, 'Target reply', $targetRootId);
            
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-' . $replyId,
            ]);

            // Should contain all root comments from pages 1-2
            expect($html)->toContain('Root comment 0');
            expect($html)->toContain('Root comment 5');
            
            // Should contain the reply
            expect($html)->toContain('Target reply');
            
            // Should have both IDs
            expect($html)->toContain('id="comment-' . $targetRootId . '"');
            expect($html)->toContain('id="comment-' . $replyId . '"');
        });
    });

    describe('Invalid Comment Handling', function () {
        it('should load normally when comment ID does not exist', function () {
            // Create some comments
            createSeveralComments(3, 'story', 123, 'Normal comment');
            
            // Mock request with non-existent comment ID
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :page="0" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-99999',
            ]);

            // 0 comments loaded
            expect($html)->not()->toMatch('/id="comment-\d+"/');
        });

        it('should load normally when comment belongs to different entity', function () {
            // Create comments for entity 123
            createSeveralComments(3, 'story', 123, 'Entity 123 comment');
            
            // Create a comment for different entity
            $otherCommentId = createComment('story', 456, 'Entity 456 comment');
            
            // Mock request with comment from different entity
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :page="0" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-' . $otherCommentId,
            ]);

            // 0 comments loaded
            expect($html)->not()->toMatch('/id="comment-\d+"/');
        });

        it('should load normally when fragment format is invalid', function () {
            // Create some comments
            createSeveralComments(3, 'story', 123, 'Normal comment');
            
            // Mock request with invalid fragment format
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :page="0" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'invalid-format',
            ]);

            // 0 comments loaded
            expect($html)->not()->toMatch('/id="comment-\d+"/');
        });

        it('should load normally when no fragment is present', function () {
            // Create some comments
            createSeveralComments(3, 'story', 123, 'Normal comment');
            
            // Mock request without fragment
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should load normally, this time, page is 1.
            expect($html)->toContain('Normal comment 0');
            expect($html)->toContain('Normal comment 2');
        });
    });

    describe('Access Control', function () {
        it('should show error for guest user even with deep link', function () {
            // Create comments
            $commentId = createComment('story', 123, 'Test comment');
            
            // Logout user (guest)
            $this->post('/logout');
            
            // Mock request with valid fragment
            $html = Blade::render('<x-comment-list entity-type="story" :entity-id="$id" :per-page="5" :fragment="$fragment" />', [
                'id' => 123,
                'fragment' => 'comment-' . $commentId,
            ]);

            // Should show members only error
            expect($html)->toContain(__('comment::comments.errors.members_only'));
            expect($html)->not()->toContain('Test comment');
        });
    });
});
