<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure container can resolve the Public API.
    $this->api = app(CommentPublicApi::class);
});

describe('POST /comments route', function () {
    it('redirects guests to login', function () {
        $response = $this->post('/comments', [
            'entity_type' => 'chapter',
            'entity_id' => 123,
            'body' => 'Hello',
        ]);

        $response->assertStatus(302);
        expect($response->headers->get('Location'))
            ->toContain('login');
    });

    it('allows confirmed users to create a comment and redirects back with flash', function () {
        // Auth as confirmed user
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        // Use a fixed intended URL to simulate back()
        $from = '/stories/some-slug/chapters/first#comment-list';
        $this->from($from);

        $payload = [
            'entity_type' => 'chapter',
            'entity_id' => 123,
            'body' => 'My first comment',
        ];

        $response = $this->post('/comments', $payload);

        // Assert redirect back and flash
        $response->assertRedirect($from);
        $response->assertSessionHas('status', __('comment::comments.posted'));
    });

    describe('Child comments', function () {

        it('should not allow to create a comment with non matching entity type', function () {
            // Auth as confirmed user
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $commentId = createComment($this->api, 'chapter', 123, 'Hello', null);

            $payload = [
                'entity_type' => 'other',
                'entity_id' => 123,
                'body' => 'My first comment',
                'parent_comment_id' => $commentId
            ];

            $response = $this->post('/comments', $payload);

            // Assert redirect back and flash
            $response->assertBadRequest();
        });

        it('should not allow to create a comment with non matching entity id', function () {
            // Auth as confirmed user
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $commentId = createComment($this->api, 'chapter', 123, 'Hello', null);

            $payload = [
                'entity_type' => 'chapter',
                'entity_id' => 456,
                'body' => 'My first comment',
                'parent_comment_id' => $commentId
            ];

            $response = $this->post('/comments', $payload);

            // Assert redirect back and flash
            $response->assertBadRequest();
        });

        it('should allow to create a comment with a parent comment', function () {
            // Auth as confirmed user
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $commentId = createComment($this->api, 'chapter', 123, 'Hello', null);

            // Use a fixed intended URL to simulate back()
            $from = '/stories/some-slug/chapters/first#comment-list';
            $this->from($from);

            $payload = [
                'entity_type' => 'chapter',
                'entity_id' => 123,
                'body' => 'My first comment',
                'parent_comment_id' => $commentId,
            ];

            $response = $this->post('/comments', $payload);

            // Assert redirect back and flash
            $response->assertRedirect($from);
            $response->assertSessionHas('status', __('comment::comments.posted'));

            $comment = listComments($this->api, 'chapter', 123);
            expect($comment->items)->toHaveCount(1);
            expect($comment->items[0]->children)->toHaveCount(1);
            expect($comment->items[0]->children[0]->body)->toContain('My first comment');
        });

        it('should not count the comment replies in the pagination', function () {
            // Auth as confirmed user
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $commentIds = createSeveralComments(5, 'chapter', 123, 'Hello', null);

            // Use a fixed intended URL to simulate back()
            $from = '/stories/some-slug/chapters/first#comment-list';
            $this->from($from);

            $payload = [
                'entity_type' => 'chapter',
                'entity_id' => 123,
                'body' => 'My first comment',
                'parent_comment_id' => $commentIds[0],
            ];

            $response = $this->post('/comments', $payload);

            // Assert redirect back and flash
            $response->assertRedirect($from);
            $response->assertSessionHas('status', __('comment::comments.posted'));

            $response = listComments($this->api, 'chapter', 123);
            // We only have 5 comments in the pagination, because the 6th one is in the replies, so will be loaded inside the first comment
            expect($response->total)->toBe(5);
            expect($response->items)->toHaveCount(5);
        });
    });
});
