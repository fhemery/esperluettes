<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Notification mark as read', function () {
    it('returns 401 for guests', function () {
        $response = $this->postJson(route('notifications.markRead', 123), []);
        $response->assertStatus(401);
    });

    it('does nothing (204) when notification does not belong to the user', function () {
        $alice = alice($this);
        $bob = bob($this);
        $this->actingAs($alice);

        // Create a notification for Bob only
        $notifId = makeNotification([$bob->id], 'test::notification.for_bob', [], $bob->id);

        $this->postJson(route('notifications.markRead', $notifId))
            ->assertNoContent();

        // Ensure there is still no read record for Alice on this notification
        $read = notificationReadRow($alice->id, $notifId);
        expect($read)->toBeNull();
    });

    it('is idempotent (204 when already read)', function () {
        $user = alice($this);
        $this->actingAs($user);

        $notifId = makeNotification([$user->id], 'test::notification.once', [], $user->id);

        // First mark
        $this->postJson(route('notifications.markRead', $notifId))
            ->assertNoContent();
        $first = notificationReadRow($user->id, $notifId);
        expect($first->read_at)->not->toBeNull();

        // Second mark (should be 204 and unchanged)
        $this->postJson(route('notifications.markRead', $notifId))
            ->assertNoContent();
        $second = notificationReadRow($user->id, $notifId);
        expect($second->read_at)->toEqual($first->read_at);
    });

    it('marks the notification as read for the owner (204)', function () {
        $user = alice($this);
        $this->actingAs($user);

        $notifId = makeNotification([$user->id], 'test::notification.mark', []);

        // Precondition: unread
        $pre = notificationReadRow($user->id, $notifId);
        expect($pre->read_at)->toBeNull();

        $this->postJson(route('notifications.markRead', $notifId))
            ->assertNoContent();

        $post = notificationReadRow($user->id, $notifId);
        expect($post->read_at)->not->toBeNull();
    });
});
