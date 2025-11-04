<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Notification index page', function () {
    it('renders the title and empty state', function () {
        $user = alice($this);
        $this->actingAs($user);

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications::pages.index.title'), false)
            ->assertSee(__('notifications::pages.index.empty'), false);
    });

    it('lists notifications ordered by created_at desc', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Older notification
        $olderAt = now()->subMinutes(10)->toDateTimeString();
        makeNotification([$user->id], null, $user->id, $olderAt);

        // Newer notification
        $newerAt = now()->toDateTimeString();
        makeNotification([$user->id], null, $user->id, $newerAt);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        // Ensure list exists and shows notifications (TestNotificationContent displays "Test notification")
        expect($html)->toContain('Test notification');
    });

    it('unread notifications are bold and show a read toggle button with gray icon', function () {
        $user = alice($this);
        $this->actingAs($user);

        makeNotification([$user->id]);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('Test notification');
        expect($html)->toContain('read-toggle-icon-unread');
    });

    it('read notifications are not bold and show a green read icon', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create, then mark as read via HTTP helper for the test
        $id = makeNotification([$user->id], null, $user->id);
        markNotificationAsRead($this, $id);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('Test notification');
        expect($html)->toContain('read-toggle-icon-read');
    });

    it('supports marking all notifications as read', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create two unread notifications
        makeNotification([$user->id], null, $user->id);
        makeNotification([$user->id], null, $user->id);

        // Mark all as read via HTTP helper
        markAllNotificationsAsRead($this);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('Test notification');
        expect($html)->toContain('read-toggle-icon-read');
        expect($html)->not->toContain('initial: false');
    });

    it('shows the actor avatar when source_user_id is present', function () {
        $alice = alice($this);
        $bob = bob($this);
        $this->actingAs($alice);

        // Create a notification for Alice performed by Bob
        makeNotification([$alice->id], null, $bob->id);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $expectedUrl = Storage::disk('public')->url('profile_pictures/' . $bob->id . '.svg');
        expect($html)->toContain($expectedUrl);
    });

    it('does not display notifications with unknown/unregistered types', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create a valid notification
        makeNotification([$user->id]);

        // Manually insert a notification with an unknown type directly into the database
        $unknownNotificationId = \Illuminate\Support\Facades\DB::table('notifications')->insertGetId([
            'source_user_id' => $user->id,
            'content_key' => 'unknown.notification.type',
            'content_data' => json_encode(['some' => 'data']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create notification_reads entry for the user
        \Illuminate\Support\Facades\DB::table('notification_reads')->insert([
            'notification_id' => $unknownNotificationId,
            'user_id' => $user->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        // Should show the valid test notification
        expect($html)->toContain('Test notification');
        
        // Should NOT show the unknown notification type
        expect($html)->not->toContain('unknown.notification.type');
        expect($html)->not->toContain('[Unknown notification type]');
        
        // Should only have 1 notification item in the list (not 2)
        $notifItemCount = substr_count($html, 'data-test-id="notif-item"');
        expect($notifItemCount)->toBe(1);
    });
});
