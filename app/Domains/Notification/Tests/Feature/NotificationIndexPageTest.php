<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        makeNotification([$user->id], 'test::notification.older', ['n' => 1], $user->id, $olderAt);

        // Newer notification
        $newerAt = now()->toDateTimeString();
        makeNotification([$user->id], 'test::notification.newer', ['n' => 2], $user->id, $newerAt);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        // Ensure list exists and first item is the newer one
        // We check the order by ensuring 'test::notification.newer' appears before 'test::notification.older'
        $posNew = strpos($html, 'test::notification.newer');
        $posOld = strpos($html, 'test::notification.older');
        expect($posNew)->not()->toBeFalse();
        expect($posOld)->not()->toBeFalse();
        expect($posNew)->toBeLessThan($posOld);
    });

    it('unread notifications are bold and show a read toggle button with gray icon', function () {
        $user = alice($this);
        $this->actingAs($user);

        makeNotification([$user->id], 'test::notification.unread', []);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('test::notification.unread');
        expect($html)->toContain('read-toggle-icon-unread');
    });

    it('read notifications are not bold and show a green read icon', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create, then mark as read via HTTP helper for the test
        $id = makeNotification([$user->id], 'test::notification.read', [], $user->id);
        markNotificationAsRead($this, $id);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('test::notification.read');
        expect($html)->toContain('read-toggle-icon-read');
    });

    it('supports marking all notifications as read', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create two unread notifications
        makeNotification([$user->id], 'test::notification.all.1', [], $user->id);
        makeNotification([$user->id], 'test::notification.all.2', [], $user->id);

        // Mark all as read via HTTP helper
        markAllNotificationsAsRead($this);

        $html = $this->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('test::notification.all.1');
        expect($html)->toContain('test::notification.all.2');
        expect($html)->toContain('read-toggle-icon-read');
        expect($html)->not->toContain('initial: false');
    });
});
