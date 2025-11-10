<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('POST /comments route', function () {
    it('redirects guests to login', function () {
        $response = $this->post('/comments', [
            'entity_type' => 'default',
            'entity_id' => 123,
            'body' => 'Hello',
        ]);

        $response->assertStatus(302);
        expect($response->headers->get('Location'))
            ->toContain('login');
    });

    it('allows confirmed users to create a comment and redirects back to comments with flash', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Use a fixed intended URL to simulate back()
        $from = '/default/123?param1=value1#anotherAnchor';
        $this->from($from);

        $payload = [
            'entity_type' => 'default',
            'entity_id' => 123,
            'body' => 'My first comment',
        ];

        $response = $this->post('/comments', $payload);

        // Assert redirect back and flash - the redirect should contain comment query parameter
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('location');
        expect($redirectUrl)->toContain('comment=');
        expect($redirectUrl)->toContain('#comments');
        expect($redirectUrl)->not()->toContain('#comments#comment-'); // Should not have double #
        $response->assertSessionHas('status', __('comment::comments.posted'));
    });


    describe('Comment Store Deep Linking', function () {
        beforeEach(function () {
            $this->user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($this->user);

            // Set up a from URL like the working test
            $this->from('/default/123?param1=value1#comments');
        });

        it('should redirect with ?comment=<id> query parameter when creating a root comment', function () {
            $response = $this->post('/comments', [
                'entity_type' => 'default',
                'entity_id' => 123,
                'body' => 'This is a new root comment',
            ]);

            $response->assertRedirect();
            
            // Should redirect to a URL containing the comment query parameter
            $redirectUrl = $response->headers->get('location');
            expect($redirectUrl)->toContain('comment=');
            expect($redirectUrl)->toContain('#comments');
            expect($redirectUrl)->toMatch('/[?&]comment=\d+#comments$/'); // Should end with ?comment=<number>#comments or &comment=<number>#comments
        });

        it('should redirect with ?comment=<id> query parameter when creating a reply comment', function () {
            // Create a parent comment first
            $parentCommentId = createComment('default', 123, 'Parent comment');

            $response = $this->post('/comments', [
                'entity_type' => 'default',
                'entity_id' => 123,
                'body' => 'This is a new reply comment',
                'parent_comment_id' => $parentCommentId,
            ]);

            $response->assertRedirect();

            // Should redirect to a URL containing the reply query parameter
            $redirectUrl = $response->headers->get('location');
            expect($redirectUrl)->toContain('comment=');
            expect($redirectUrl)->toContain('#comments');
            expect($redirectUrl)->toMatch('/[?&]comment=\d+#comments$/'); // Should end with ?comment=<number>#comments or &comment=<number>#comments
        });

        it('should preserve existing query parameters when adding comment parameter', function () {
            // Simulate coming from a page with query parameters
            $response = $this->post('/comments', [
                'entity_type' => 'default',
                'entity_id' => 123,
                'body' => 'Comment with query params',
            ]);

            $response->assertRedirect();

            $redirectUrl = $response->headers->get('location');

            // Should contain the comment query parameter
            expect($redirectUrl)->toContain('&comment=');
            expect($redirectUrl)->toContain('#comments');

            // Should preserve the base redirect structure (path and query params)
            expect($redirectUrl)->toContain('./default/123?param1=value1');
        });

        it('should work even when validation fails (no redirect with comment parameter)', function () {
            $response = $this->post('/comments', [
                'entity_type' => 'default',
                'entity_id' => 123,
                'body' => '', // Empty body should fail validation
            ]);

            // Should redirect back without comment parameter on validation failure
            $response->assertRedirect();
            $redirectUrl = $response->headers->get('location');
            expect($redirectUrl)->not()->toContain('comment=');
        });
    });
});
