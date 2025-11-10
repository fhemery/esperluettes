<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CommentListComponent', function () {
    describe('Access', function () {
        it('should display an alert if user is not logged, with a login button redirecting directly to comment area', function () {
            Auth::logout();
            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain(__('comment::comments.errors.members_only'));
            expect($html)->toContain(__('comment::comments.actions.login'));
            // Regex: ensure link contains login-intended and encoded #comments anchor
            expect($html)->toMatch('/login-intended\?redirect=[^"\s>]*%23comments/');
            expect($html)->not()->toContain(__('comment::comments.list.empty'));
            expect($html)->not()->toContain('<form');
        });

        it('should display an alert if user is not verified', function () {
            $user = alice($this, roles: [], isVerified: false);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain(__('comment::comments.errors.members_only'));
            expect($html)->not()->toContain(__('comment::comments.actions.login'));
            expect($html)->not()->toContain(__('comment::comments.list.empty'));
            expect($html)->not()->toContain('<form');
        });
    });

    describe('Content', function () {
        it('renders the Comment list component without comments', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain(__('comment::comments.list.empty'));
        });

        it('renders the Comment list component with comments', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            // Seed one comment
            createComment('default', 123, 'Hello world', null);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);

            expect($html)->toContain('Hello world');
        });
    });

    describe('When policies are in place', function () {
        it('should show a minimum number of character in the editor if specified ', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function getRootCommentMinLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);


            expect($html)->toContain(__('shared::editor.min-characters', ['count' => 10]));
        });

        it('should show a maximum number of character in the editor if specified ', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function getRootCommentMaxLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);


            expect($html)->toContain('/ 10');
        });

        it('should not show the form is root posting is disabled', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function canCreateRoot(int $entityId, int $userId): bool
                {
                    return false;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            $html = Blade::render('<x-comment::comment-list-component entity-type="default" :entity-id="$id" :per-page="10" />', [
                'id' => 123,
            ]);


            expect($html)->not()->toContain('<form');
        });
    });

    describe('Comment Share Button', function () {
        beforeEach(function () {
            $this->user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($this->user);
        });

        it('should display share button for each comment', function () {
            // Create a comment
            $commentId = createComment('story', 123, 'Test comment for sharing');

            // Render the comment list
            $html = Blade::render('<x-comment::comment-list-component entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should contain share button elements
            expect($html)->toContain('share');
            expect($html)->toContain('comment::comments.actions.share');
            expect($html)->toContain('comment::comments.actions.copied');
            expect($html)->toContain('navigator.clipboard.writeText');
        });

        it('should include share functionality JavaScript', function () {
            // Create a comment
            $commentId = createComment('story', 123, 'Test comment');

            // Render the comment list
            $html = Blade::render('<x-comment::comment-list-component entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should contain the JavaScript for copying functionality
            expect($html)->toContain('navigator.clipboard.writeText');
            expect($html)->toContain('url.searchParams.set(\'comment\'');
            expect($html)->toContain('url.hash = \'comments\'');
        });

        it('should work for both root comments and replies', function () {
            // Create a root comment and a reply
            $rootCommentId = createComment('story', 123, 'Root comment');
            $replyId = createComment('story', 123, 'Reply comment', $rootCommentId);

            // Render the comment list
            $html = Blade::render('<x-comment::comment-list-component entity-type="story" :entity-id="$id" :per-page="5" />', [
                'id' => 123,
            ]);

            // Should contain share buttons for both comments
            expect($html)->toContain('comment::comments.actions.share');

            // Should have the correct comment IDs in the JavaScript
            expect($html)->toContain('url.searchParams.set(\'comment\', ' . $rootCommentId);
            expect($html)->toContain('url.searchParams.set(\'comment\', ' . $replyId);
        });
    });
});
