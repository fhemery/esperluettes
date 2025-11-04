<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Notification Load More', function () {
    it('requires authentication', function () {
        $this->get(route('notifications.loadMore', ['offset' => 0]))
            ->assertRedirect(route('login'));
    });

    it('returns HTML fragment with notification items when offset is 0', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create 3 notifications
        makeNotification([$user->id]);
        makeNotification([$user->id]);
        makeNotification([$user->id]);

        $response = $this->get(route('notifications.loadMore', ['offset' => 0]));

        $response->assertOk();
        
        // Should contain notification items (check for test notification content)
        $html = $response->getContent();
        expect($html)->toContain('Test notification');
        expect($html)->toContain('data-test-id="notif-item"');
        
        // Count occurrences - should have 3 items
        $count = substr_count($html, 'data-test-id="notif-item"');
        expect($count)->toBe(3);
    });

    it('returns notifications starting from the given offset', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create 25 notifications (more than one page of 20)
        for ($i = 0; $i < 25; $i++) {
            makeNotification([$user->id], null, $user->id, now()->subMinutes(25 - $i)->toDateTimeString());
        }

        // Get first page (offset 0, limit 20) - should return 20 items
        $response1 = $this->get(route('notifications.loadMore', ['offset' => 0]));
        $html1 = $response1->getContent();
        $count1 = substr_count($html1, '<li class="py-2 w-full grid grid-cols-[auto_1fr_auto] gap-4 notif-item"');
        expect($count1)->toBe(20);

        // Get second page (offset 20, limit 20) - should return 5 remaining items
        $response2 = $this->get(route('notifications.loadMore', ['offset' => 20]));
        $html2 = $response2->getContent();
        $count2 = substr_count($html2, '<li class="py-2 w-full grid grid-cols-[auto_1fr_auto] gap-4 notif-item"');
        expect($count2)->toBe(5);
    });

    it('returns empty HTML when offset exceeds total notifications', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create only 2 notifications
        makeNotification([$user->id]);
        makeNotification([$user->id]);

        // Request with offset 20 (beyond available notifications)
        $response = $this->get(route('notifications.loadMore', ['offset' => 20]));

        $response->assertOk();
        $html = $response->getContent();
        
        // Should be empty or minimal
        expect(trim($html))->toBe('');
    });

    it('includes X-Has-More header when more notifications exist', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create 25 notifications (more than 20)
        for ($i = 0; $i < 25; $i++) {
            makeNotification([$user->id]);
        }

        // Get first page (offset 0) - should indicate more exist
        $response = $this->get(route('notifications.loadMore', ['offset' => 0]));
        
        $response->assertHeader('X-Has-More', 'true');
    });

    it('includes X-Has-More=false header when no more notifications exist', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create exactly 20 notifications
        for ($i = 0; $i < 20; $i++) {
            makeNotification([$user->id]);
        }

        // Get first page (offset 0) - should indicate no more exist
        $response = $this->get(route('notifications.loadMore', ['offset' => 0]));
        
        $response->assertHeader('X-Has-More', 'false');
    });

    it('renders notification items with avatar when source_user_id is present', function () {
        $alice = alice($this);
        $bob = bob($this);
        $this->actingAs($alice);

        // Create notification from bob to alice
        makeNotification([$alice->id], null, $bob->id);

        $response = $this->get(route('notifications.loadMore', ['offset' => 0]));

        $html = $response->getContent();
        
        // Should contain bob's avatar URL
        expect($html)->toContain('profile_pictures/' . $bob->id . '.svg');
    });

    it('orders notifications by created_at desc', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Create 3 notifications with specific timestamps
        $old = makeNotification([$user->id], null, $user->id, now()->subHours(3)->toDateTimeString());
        $middle = makeNotification([$user->id], null, $user->id, now()->subHours(2)->toDateTimeString());
        $recent = makeNotification([$user->id], null, $user->id, now()->subHours(1)->toDateTimeString());

        $response = $this->get(route('notifications.loadMore', ['offset' => 0]));

        $html = $response->getContent();
        
        // Find positions of notification IDs in HTML
        $recentPos = strpos($html, 'data-notification-id="' . $recent . '"');
        $middlePos = strpos($html, 'data-notification-id="' . $middle . '"');
        $oldPos = strpos($html, 'data-notification-id="' . $old . '"');
        
        // Recent should come before middle, middle before old
        expect($recentPos)->toBeLessThan($middlePos);
        expect($middlePos)->toBeLessThan($oldPos);
    });
});
