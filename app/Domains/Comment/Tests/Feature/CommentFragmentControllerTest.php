<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Comment list partial display', function () {
    it('renders root comments HTML for the first page', function () {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this, roles: [Roles::USER]);
        $this->actingAs($user);

        // Create 3 root comments: Hello 0, Hello 1, Hello 2
        createSeveralComments(3, $entityType, $entityId, 'Hello');

        // PerPage = 2 to force pagination
        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        // Only the 2 most recent should be present (created_at desc): Hello 2 and Hello 1
        $response->assertSee('Hello 2', false);
        $response->assertSee('Hello 1', false);
        $response->assertDontSee('Hello 0', false);
        // Next page header should be set
        $response->assertHeader('X-Next-Page', '2');
    });

    it('displays Posted at but not edited at for comments that have not been edited', function() {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this, roles: [Roles::USER]);
        $this->actingAs($user);

        createComment($entityType, $entityId, 'Hello');

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertDontSee(__('comment::comments.updated_at'));
    });

    it('displays Posted at and edited at for comments that have been edited', function() {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this, roles: [Roles::USER]);
        $this->actingAs($user);

        $commentId = createComment($entityType, $entityId, 'Hello');
        // Because test execute fast, we need to update the comment created at date
        Comment::query()->where('id', $commentId)->update(['created_at' => now()->subMinutes(1)]);
        editComment($commentId, 'Hello edited');

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('comment::comments.updated_at'));
    });

    it('should put a profile link on each comment author', function () {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this);
        $this->actingAs($user);

        createComment($entityType, $entityId, 'Hello');

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['<a href="' . route('profile.show', ['profile' => 'alice']), 'Hello'], false);
    });

    it('renders default avatar and translated name when author is unknown', function () {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this);
        $this->actingAs($user);

        // Create a comment normally, then simulate an unknown author by nulling author_id
        $commentId = createComment($entityType, $entityId, 'Anonymous says hello');
        Comment::query()->where('id', $commentId)->update(['author_id' => null]);

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        // Default avatar image should be used
        $response->assertSee('images/default-avatar.svg', false);
        // Default translated name should be displayed
        $response->assertSee(__('comment::comments.unknown_user'));
    });

    it('renders child comments HTML for the first page', function () {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this, roles: [Roles::USER]);
        $this->actingAs($user);

        // Create 3 root comments: Hello 0, Hello 1, Hello 2
        $commentId = createComment($entityType, $entityId, 'Hello');
        createComment($entityType, $entityId, 'Hello from child', $commentId);

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Hello from child', false);
    });

    it('returns 401 for guests (no role) when listing fragments', function () {
        $entityType = 'default';
        $entityId = 1;

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]));

        $response->assertStatus(401);
    });

    it('should render the Reply button only on the last child of a root comment', function () {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this);
        $this->actingAs($user);

        // Create 3 root comments: Hello 0, Hello 1, Hello 2
        $commentId = createComment($entityType, $entityId, 'Hello');
        $childComment1Id = createComment($entityType, $entityId, 'Hello from child', $commentId);
        $childComment2Id = createComment($entityType, $entityId, 'Hello from child 2', $commentId);

        // Adjust times to be sure of the display order
        Comment::query()->where('id', $childComment1Id)->update(['created_at' => now()->subMinutes(1)]);
        Comment::query()->where('id', $childComment2Id)->update(['created_at' => now()]);

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('data-action="reply" data-comment-id="' . $childComment1Id . '"', false);
        $response->assertDontSee('data-action="reply" data-comment-id="' . $childComment2Id . '"', false);

        // There is only one reply button, and it is after the last comment
        expect(substr_count($response->getContent(), 'data-action="reply" data-comment-id="' . $commentId . '"'))->toBe(1);
        $response->assertSeeInOrder([
            'Hello from child 2',
            'data-action="reply" data-comment-id="' . $commentId . '"',
        ]);
    });

    it('should show the edit button is current user is the author', function () {
        $entityType = 'default';
        $entityId = 123;

        $user = alice($this);
        $this->actingAs($user);
        $aliceCommentId = createComment($entityType, $entityId, 'Hello');

        $otherUser = bob($this);
        $this->actingAs($otherUser);
        $bobCommentId = createComment($entityType, $entityId, 'Hello from bob', $aliceCommentId);

        $response = $this->get(route('comments.fragments', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('data-action="edit" data-comment-id="' . $aliceCommentId . '"', false);
        $response->assertSee('data-action="edit" data-comment-id="' . $bobCommentId . '"', false);
    });

    describe('When policies are in place', function () {
        it('should show a minimum number of character in the editor if specified ', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function getReplyCommentMinLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            createComment($entityType, 123, 'Hello world!');

            $response = $this->get(route('comments.fragments', [
                'entity_type' => $entityType,
                'entity_id' => 123,
                'page' => 1,
                'per_page' => 2,
            ]));

            $response->assertSee(__('shared::editor.min-characters', ['count' => 10]));
        });

        it('should show a maximum number of character in the editor if specified ', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function getReplyCommentMaxLength(): ?int
                {
                    return 242;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            createComment($entityType, 123, 'Hello');

            $response = $this->get(route('comments.fragments', [
                'entity_type' => $entityType,
                'entity_id' => 123,
                'page' => 1,
                'per_page' => 2,
            ]));


            $response->assertSee('/ 242');
        });

        it('should not show the edit button if edit is forbidden by policy', function () {
            $entityType = 'default';
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register($entityType, new class extends DefaultCommentPolicy {
                public function canEditOwn(CommentDto $comment, int $userId): bool
                {
                    return false;
                }
            });

            $user = alice($this);
            $this->actingAs($user);

            createComment($entityType, 123, 'Hello');

            $response = $this->get(route('comments.fragments', [
                'entity_type' => $entityType,
                'entity_id' => 123,
                'page' => 1,
                'per_page' => 2,
            ]));


            $response->assertDontSee('data-action="edit"', false);
        });
    });

    describe('Moderation UI in fragments', function () {
        beforeEach(function () {
            createFeatureToggle($this, new FeatureToggle(
                'reporting',
                'moderation',
                access: FeatureToggleAccess::ON
            ));
        });

        it('shows the report button to authenticated non-authors in fragment HTML', function () {
            $entityType = 'default';
            $entityId = 789;

            $author = alice($this);
            $viewer = bob($this);
            $this->actingAs($author);
            createComment($entityType, $entityId, 'Hello world');

            // Viewer requests the fragment (not author)
            $this->actingAs($viewer);
            $resp = $this->get(route('comments.fragments', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'page' => 1,
                'per_page' => 10,
            ]));

            $resp->assertOk();
            // Compact report button sets the title attribute to the translated text
            $resp->assertSee(e(__('moderation::report.button')), false);
        });

        it('does not show the report button to the comment author', function () {
            $entityType = 'default';
            $entityId = 790;

            $author = alice($this);
            $this->actingAs($author);
            createComment($entityType, $entityId, 'Author comment');

            $resp = $this->get(route('comments.fragments', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'page' => 1,
                'per_page' => 10,
            ]));

            $resp->assertOk();
            // Should not render the compact report button title for the author
            $resp->assertDontSee(e(__('moderation::report.button')));
        });

        it('shows the moderator popover to moderators in fragment HTML', function () {
            $entityType = 'default';
            $entityId = 791;

            $author = alice($this);
            $moderator = moderator($this);
            $this->actingAs($author);
            createComment($entityType, $entityId, 'Moderate me');

            // Moderator requests the fragment
            $this->actingAs($moderator);
            $resp = $this->get(route('comments.fragments', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'page' => 1,
                'per_page' => 10,
            ]));

            $resp->assertOk();
            $resp->assertSee('id="comment-moderator-btn"', false);
        });
    });
});
