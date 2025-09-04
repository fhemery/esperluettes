<?php

use App\Domains\Auth\PublicApi\Roles;
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

        // Assert redirect back and flash
        $response->assertRedirect('./default/123?param1=value1#comments');
        $response->assertSessionHas('status', __('comment::comments.posted'));
    });
  
});
