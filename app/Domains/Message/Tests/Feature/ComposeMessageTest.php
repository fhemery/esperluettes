<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Message\Private\Models\MessageDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Compose Message', function () {
    describe('Page access', function () {


        it('allows admins to access the compose page', function () {
            $admin = admin($this);

            $this->actingAs($admin);
            $response = $this->get(route('messages.compose'));

            $response->assertStatus(200);
            $response->assertSee(__('message::messages.compose_title'));
        });

        it('prevents regular users from composing messages', function () {
            $user = alice($this);

            $this->actingAs($user);
            $response = $this->post(route('messages.store'), [
                'title' => 'Test',
                'content' => '<p>Content</p>',
                'target_users' => [],
                'target_roles' => [],
                'target_everyone' => false,
            ]);

            $response->assertStatus(302);   // Redirect to dashboard
        });
    });

    it('allows admin to send a message to specific users', function () {
        $sender = admin($this);
        $recipient1 = alice($this);
        $recipient2 = bob($this);

        $this->actingAs($sender);
        
        $response = $this->post('/messages', [
            'title' => 'Test Message',
            'content' => '<p>Hello world!</p>',
            'target_users' => [$recipient1->id, $recipient2->id],
            'target_roles' => [],
        ]);

        $response->assertRedirect('/messages');
        
        $deliveries = MessageDelivery::whereIn('user_id', [$recipient1->id, $recipient2->id])->get();
        expect($deliveries)->toHaveCount(2);
        expect($deliveries->first()->message->title)->toBe('Test Message');
        expect($deliveries->first()->message->sent_by_id)->toBe($sender->id);
    });

    it('allows admin to send a message to users with a specific role', function () {
        $sender = admin($this);
        $alice = alice($this); // user-confirmed
        $bob = bob($this);     // user-confirmed
        
        $this->actingAs($sender);
        
        $response = $this->post('/messages', [
            'title' => 'Message to Confirmed Users',
            'content' => '<p>Hello confirmed users!</p>',
            'target_users' => [],
            'target_roles' => [Roles::USER_CONFIRMED],
            'target_everyone' => false,
        ]);

        $response->assertRedirect('/messages');
        
        $deliveries = MessageDelivery::whereIn('user_id', [$alice->id, $bob->id])->get();
        expect($deliveries->count())->toBeGreaterThanOrEqual(2);
        expect($deliveries->pluck('user_id')->toArray())->toContain($alice->id);
        expect($deliveries->pluck('user_id')->toArray())->toContain($bob->id);
    });

    it('does not create duplicate deliveries for the same user', function () {
        $sender = admin($this);
        $recipient = alice($this);
        
        $this->actingAs($sender);
        
        // Send to same user via both direct selection AND role
        $response = $this->post('/messages', [
            'title' => 'Test Message',
            'content' => '<p>Hello!</p>',
            'target_users' => [$recipient->id],
            'target_roles' => [Roles::USER_CONFIRMED],
            'target_everyone' => false,
        ]);

        $response->assertRedirect('/messages');
        
        $deliveries = MessageDelivery::where('user_id', $recipient->id)->get();
        expect($deliveries)->toHaveCount(1);
    });

    it('returns error when no recipients are selected', function () {
        $admin = admin($this);

        $this->actingAs($admin);
        $response = $this->post('/messages', [
            'title' => 'No Recipients',
            'content' => '<p>Content</p>',
            'target_users' => [],
            'target_roles' => [],
            'target_everyone' => false,
        ]);

        $response->assertSessionHasErrors('recipients');
    });

    it('purifies message content with strict profile', function () {
        $admin = admin($this);
        $recipient = alice($this);

        $this->actingAs($admin);
        $response = $this->post('/messages', [
            'title' => 'Test',
            'content' => '<p>Hello</p><script>alert("xss")</script>',
            'target_users' => [$recipient->id],
            'target_roles' => [],
            'target_everyone' => false,
        ]);

        $response->assertRedirect('/messages');

        $delivery = MessageDelivery::where('user_id', $recipient->id)->first();
        expect($delivery->message->content)->not->toContain('<script>');
        expect($delivery->message->content)->toContain('<p>Hello</p>');
    });
});
