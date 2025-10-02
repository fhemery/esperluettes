<?php

use App\Domains\Message\Private\Models\MessageDelivery;
use App\Domains\Message\Private\Services\MessageDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Message Controller', function () {

    describe('Page access', function () {
        it('redirects guests to login when accessing messages', function () {
            $response = $this->get('/messages');

            $response->assertStatus(302);
            expect($response->headers->get('Location'))->toContain('login');
        });

        it('allows authenticated users to view their messages list', function () {
            $sender = admin($this);
            $recipient = alice($this);

            sendMessageToUsers($this, $sender, 'Test Message', '<p>Hello!</p>', [$recipient->id]);

            $this->actingAs($recipient);
            $response = $this->get('/messages');

            $response->assertStatus(200);
            $response->assertSee('Test Message');
        });
    });

    describe('Message actions', function () {
        it('marks a message as read when viewing it', function () {
            $sender = admin($this);
            $recipient = alice($this);

            $service = app(MessageDispatchService::class);
            $message = $service->dispatch(
                sentById: $sender->id,
                title: 'Test Message',
                content: '<p>Hello!</p>',
                recipientIds: [$recipient->id]
            );

            $delivery = MessageDelivery::where('message_id', $message->id)
                ->where('user_id', $recipient->id)
                ->first();

            expect($delivery->is_read)->toBeFalse();

            $this->actingAs($recipient);
            $response = $this->get("/messages/{$delivery->id}");

            $response->assertStatus(200);

            $delivery->refresh();
            expect($delivery->is_read)->toBeTrue();
            expect($delivery->read_at)->not->toBeNull();
        });

        it('prevents users from viewing messages that do not belong to them', function () {
            $sender = admin($this);
            $recipient1 = alice($this);
            $recipient2 = bob($this);

            $service = app(MessageDispatchService::class);
            $message = $service->dispatch(
                sentById: $sender->id,
                title: 'Private Message',
                content: '<p>For Alice only!</p>',
                recipientIds: [$recipient1->id]
            );

            $delivery = MessageDelivery::where('message_id', $message->id)
                ->where('user_id', $recipient1->id)
                ->first();

            // Bob tries to view Alice's message
            $this->actingAs($recipient2);
            $response = $this->get("/messages/{$delivery->id}");

            $response->assertStatus(403);
        });

        it('allows users to delete their own message delivery', function () {
            $sender = admin($this);
            $recipient1 = alice($this);
            $recipient2 = bob($this);

            $service = app(MessageDispatchService::class);
            $message = $service->dispatch(
                sentById: $sender->id,
                title: 'Test Message',
                content: '<p>Hello!</p>',
                recipientIds: [$recipient1->id, $recipient2->id]
            );

            $delivery1 = MessageDelivery::where('message_id', $message->id)
                ->where('user_id', $recipient1->id)
                ->first();

            $this->actingAs($recipient1);
            $response = $this->delete("/messages/{$delivery1->id}");

            $response->assertRedirect('/messages');
            expect(MessageDelivery::find($delivery1->id))->toBeNull();

            // Bob's delivery should still exist
            $delivery2 = MessageDelivery::where('message_id', $message->id)
                ->where('user_id', $recipient2->id)
                ->first();
            expect($delivery2)->not->toBeNull();
        });

        it('prevents users from deleting messages that do not belong to them', function () {
            $sender = admin($this);
            $recipient1 = alice($this);
            $recipient2 = bob($this);

            $service = app(MessageDispatchService::class);
            $message = $service->dispatch(
                sentById: $sender->id,
                title: 'Private Message',
                content: '<p>For Alice only!</p>',
                recipientIds: [$recipient1->id]
            );

            $delivery = MessageDelivery::where('message_id', $message->id)
                ->where('user_id', $recipient1->id)
                ->first();

            // Bob tries to delete Alice's message
            $this->actingAs($recipient2);
            $response = $this->delete("/messages/{$delivery->id}");

            $response->assertStatus(403);
            expect(MessageDelivery::find($delivery->id))->not->toBeNull();
        });
    });
});
