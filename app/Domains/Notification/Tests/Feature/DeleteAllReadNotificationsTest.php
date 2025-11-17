<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Delete all read notifications', function () {
    it('returns 302 redirect for guests', function () {
        $response = $this->post(route('notifications.deleteAllRead'), []);
        $response->assertStatus(302);
    });

    it('deletes only read notification rows for authenticated user', function () {
        $alice = alice($this);
        $bob = bob($this);
        $this->actingAs($alice);

        // Create notifications for Alice
        $aliceNotif1 = makeNotification([$alice->id]);
        $aliceNotif2 = makeNotification([$alice->id]);
        $aliceNotif3 = makeNotification([$alice->id]);

        // Create notifications for Bob (should not be affected)
        $bobNotif1 = makeNotification([$bob->id]);

        // Mark Alice's first two notifications as read
        markNotificationAsRead($this, $aliceNotif1);
        markNotificationAsRead($this, $aliceNotif2);
        // Leave aliceNotif3 as unread

        // Verify initial state
        expect(notificationReadRow($alice->id, $aliceNotif1))->read_at->not->toBeNull();
        expect(notificationReadRow($alice->id, $aliceNotif2))->read_at->not->toBeNull();
        expect(notificationReadRow($alice->id, $aliceNotif3))->read_at->toBeNull();
        expect(notificationReadRow($bob->id, $bobNotif1))->read_at->toBeNull();

        // Delete all read notifications for Alice
        $this->post(route('notifications.deleteAllRead'))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Verify Alice's read notifications are deleted
        expect(notificationReadRow($alice->id, $aliceNotif1))->toBeNull();
        expect(notificationReadRow($alice->id, $aliceNotif2))->toBeNull();
        // Alice's unread notification should remain
        expect(notificationReadRow($alice->id, $aliceNotif3))->read_at->toBeNull();
        // Bob's notification should be unaffected
        expect(notificationReadRow($bob->id, $bobNotif1))->read_at->toBeNull();
    });

    it('does nothing when user has no read notifications', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create unread notifications
        $notif1 = makeNotification([$user->id]);
        $notif2 = makeNotification([$user->id]);

        // Verify initial state
        expect(notificationReadRow($user->id, $notif1))->read_at->toBeNull();
        expect(notificationReadRow($user->id, $notif2))->read_at->toBeNull();

        // Delete all read notifications
        $this->post(route('notifications.deleteAllRead'))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Verify unread notifications are still there
        expect(notificationReadRow($user->id, $notif1))->read_at->toBeNull();
        expect(notificationReadRow($user->id, $notif2))->read_at->toBeNull();
    });

    it('deletes all read notifications when all are read', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create notifications and mark all as read
        $notif1 = makeNotification([$user->id]);
        $notif2 = makeNotification([$user->id]);
        $notif3 = makeNotification([$user->id]);

        markNotificationAsRead($this, $notif1);
        markNotificationAsRead($this, $notif2);
        markNotificationAsRead($this, $notif3);

        // Verify initial state
        expect(notificationReadRow($user->id, $notif1))->read_at->not->toBeNull();
        expect(notificationReadRow($user->id, $notif2))->read_at->not->toBeNull();
        expect(notificationReadRow($user->id, $notif3))->read_at->not->toBeNull();

        // Delete all read notifications
        $this->post(route('notifications.deleteAllRead'))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Verify all notifications are deleted
        expect(notificationReadRow($user->id, $notif1))->toBeNull();
        expect(notificationReadRow($user->id, $notif2))->toBeNull();
        expect(notificationReadRow($user->id, $notif3))->toBeNull();
    });

    it('preserves notifications when user has mixed read/unread across different notification types', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create multiple notifications
        $notif1 = makeNotification([$user->id]);
        $notif2 = makeNotification([$user->id]);
        $notif3 = makeNotification([$user->id]);

        // Mark some as read
        markNotificationAsRead($this, $notif1);
        markNotificationAsRead($this, $notif3);
        // Leave notif2 as unread

        // Delete all read notifications
        $this->post(route('notifications.deleteAllRead'))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Verify only read notifications are deleted
        expect(notificationReadRow($user->id, $notif1))->toBeNull();
        expect(notificationReadRow($user->id, $notif2))->read_at->toBeNull(); // Should remain
        expect(notificationReadRow($user->id, $notif3))->toBeNull();
    });
});
