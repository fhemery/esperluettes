<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Notification mark as unread', function () {
    it('returns 401 for guests', function () {
        $response = $this->postJson(route('notifications.markUnread', 123), []);
        $response->assertStatus(401);
    });

    it('does nothing (204) when notification does not belong to the user', function () {
        $alice = alice($this);
        $bob = bob($this);
        $this->actingAs($alice);

        // Create a notification for Bob and mark it read for Bob (owner)
        $notifId = makeNotification([$bob->id], null, $bob->id);
        $this->actingAs($bob);
        markNotificationAsRead($this, $notifId);

        // Alice tries to mark Bob's notif as unread
        $this->actingAs($alice);
        $this->postJson(route('notifications.markUnread', $notifId))
            ->assertNoContent();

        // Ensure no read row for Alice, and Bob remains read
        $aliceRow = notificationReadRow($alice->id, $notifId);
        expect($aliceRow)->toBeNull();
        $bobRow = notificationReadRow($bob->id, $notifId);
        expect($bobRow->read_at)->not->toBeNull();
    });

    it('is idempotent (204 when already unread)', function () {
        $user = alice($this);
        $this->actingAs($user);

        $notifId = makeNotification([$user->id], null, $user->id);
        // Already unread, calling unread should be 204 and remain null
        $this->postJson(route('notifications.markUnread', $notifId))
            ->assertNoContent();
        $row = notificationReadRow($user->id, $notifId);
        expect($row->read_at)->toBeNull();
    });

    it('marks the notification as unread for the owner (204)', function () {
        $user = alice($this);
        $this->actingAs($user);

        $notifId = makeNotification([$user->id], null, $user->id);
        // Mark read first
        markNotificationAsRead($this, $notifId);
        $pre = notificationReadRow($user->id, $notifId);
        expect($pre->read_at)->not->toBeNull();

        // Now mark unread
        markNotificationAsUnread($this, $notifId);
        $post = notificationReadRow($user->id, $notifId);
        expect($post->read_at)->toBeNull();
    });
});
