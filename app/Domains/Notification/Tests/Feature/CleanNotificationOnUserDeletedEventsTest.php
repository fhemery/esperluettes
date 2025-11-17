<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Auth\Public\Events\UserDeleted;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Clean notifications on user deleted event', function () {
    it('removes notification_reads rows for deleted user', function () {
        $alice = alice($this);
        $bob = bob($this);
        $carol = carol($this);

        // Create notifications for Alice (to be deleted)
        $aliceNotif1 = makeNotification([$alice->id]);
        $aliceNotif2 = makeNotification([$alice->id]);

        // Create notifications for Bob and Carol (should remain)
        $bobNotif1 = makeNotification([$bob->id]);
        $carolNotif1 = makeNotification([$carol->id]);

        // Verify initial state - all users have notification_reads rows
        expect(notificationReadRow($alice->id, $aliceNotif1))->not->toBeNull();
        expect(notificationReadRow($alice->id, $aliceNotif2))->not->toBeNull();
        expect(notificationReadRow($bob->id, $bobNotif1))->not->toBeNull();
        expect(notificationReadRow($carol->id, $carolNotif1))->not->toBeNull();

        // Fire UserDeleted event for Alice
        event(new UserDeleted($alice->id));

        // Verify Alice's notification_reads rows are deleted
        expect(notificationReadRow($alice->id, $aliceNotif1))->toBeNull();
        expect(notificationReadRow($alice->id, $aliceNotif2))->toBeNull();
        // Bob and Carol's notification_reads should remain
        expect(notificationReadRow($bob->id, $bobNotif1))->not->toBeNull();
        expect(notificationReadRow($carol->id, $carolNotif1))->not->toBeNull();
    });

    it('removes notifications where deleted user was the source', function () {
        $alice = alice($this);
        $bob = bob($this);
        $carol = carol($this);

        // Create notifications with Alice as source (to be deleted)
        $aliceSourceNotif1 = makeNotification([$bob->id, $carol->id], null, $alice->id);
        $aliceSourceNotif2 = makeNotification([$bob->id], null, $alice->id);

        // Create notifications with Bob as source (should remain)
        $bobSourceNotif1 = makeNotification([$alice->id, $carol->id], null, $bob->id);

        // Verify initial state - all notifications exist
        expect(notificationExists($aliceSourceNotif1))->toBeTrue();
        expect(notificationExists($aliceSourceNotif2))->toBeTrue();
        expect(notificationExists($bobSourceNotif1))->toBeTrue();

        // Verify notification_reads rows exist
        expect(notificationReadRow($bob->id, $aliceSourceNotif1))->not->toBeNull();
        expect(notificationReadRow($carol->id, $aliceSourceNotif1))->not->toBeNull();
        expect(notificationReadRow($bob->id, $aliceSourceNotif2))->not->toBeNull();
        expect(notificationReadRow($alice->id, $bobSourceNotif1))->not->toBeNull();
        expect(notificationReadRow($carol->id, $bobSourceNotif1))->not->toBeNull();

        // Fire UserDeleted event for Alice
        event(new UserDeleted($alice->id));

        // Verify Alice's source notifications are deleted
        expect(notificationExists($aliceSourceNotif1))->toBeFalse();
        expect(notificationExists($aliceSourceNotif2))->toBeFalse();
        // Bob's source notification should remain
        expect(notificationExists($bobSourceNotif1))->toBeTrue();

        // Verify related notification_reads rows are also deleted (cascade)
        expect(notificationReadRow($bob->id, $aliceSourceNotif1))->toBeNull();
        expect(notificationReadRow($carol->id, $aliceSourceNotif1))->toBeNull();
        expect(notificationReadRow($bob->id, $aliceSourceNotif2))->toBeNull();
        // Alice's notification_reads should be deleted (user is deleted)
        expect(notificationReadRow($alice->id, $bobSourceNotif1))->toBeNull();
        // Carol's notification_reads should remain
        expect(notificationReadRow($carol->id, $bobSourceNotif1))->not->toBeNull();
    });

    it('handles user with no notifications gracefully', function () {
        $alice = alice($this);
        $bob = bob($this);

        // Create notifications for Bob only
        $bobNotif1 = makeNotification([$bob->id]);

        // Verify initial state
        expect(notificationReadRow($bob->id, $bobNotif1))->not->toBeNull();

        // Fire UserDeleted event for Alice (who has no notifications)
        event(new UserDeleted($alice->id));

        // Bob's notifications should remain unchanged
        expect(notificationReadRow($bob->id, $bobNotif1))->not->toBeNull();
    });

    it('handles user who is both target and source of notifications', function () {
        $alice = alice($this);
        $bob = bob($this);

        // Alice receives notifications from others
        $bobToAliceNotif = makeNotification([$alice->id], null, $bob->id);

        // Alice sends notifications to others
        $aliceToBobNotif = makeNotification([$bob->id], null, $alice->id);

        // Alice also receives her own notifications
        $aliceToSelfNotif = makeNotification([$alice->id], null, $alice->id);

        // Verify initial state
        expect(notificationReadRow($alice->id, $bobToAliceNotif))->not->toBeNull();
        expect(notificationReadRow($bob->id, $aliceToBobNotif))->not->toBeNull();
        expect(notificationReadRow($alice->id, $aliceToSelfNotif))->not->toBeNull();
        expect(notificationExists($aliceToBobNotif))->toBeTrue();
        expect(notificationExists($aliceToSelfNotif))->toBeTrue();

        // Fire UserDeleted event for Alice
        event(new UserDeleted($alice->id));

        // Alice's notification_reads rows should be deleted
        expect(notificationReadRow($alice->id, $bobToAliceNotif))->toBeNull();
        expect(notificationReadRow($alice->id, $aliceToSelfNotif))->toBeNull();

        // Alice's source notifications should be deleted
        expect(notificationExists($aliceToBobNotif))->toBeFalse();
        expect(notificationExists($aliceToSelfNotif))->toBeFalse();

        // Related notification_reads should be deleted
        expect(notificationReadRow($bob->id, $aliceToBobNotif))->toBeNull();

        // Bob's notification from Alice should remain (it was sent by Bob, not Alice)
        expect(notificationExists($bobToAliceNotif))->toBeTrue();
    });
});
