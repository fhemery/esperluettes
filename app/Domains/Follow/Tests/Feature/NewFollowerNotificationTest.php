<?php

use App\Domains\Follow\Private\Notifications\NewFollowerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('New follower notification', function () {
    it('sends a notification to the followed user when someone follows them', function () {
        $follower = bob($this);
        $followed = alice($this);

        $this->actingAs($follower)
            ->post(route('follow.follow', $followed->id));

        $notification = getLatestNotificationByKey(NewFollowerNotification::type());
        expect($notification)->not->toBeNull();

        $targetIds = getNotificationTargetUserIds($notification->id);
        expect($targetIds)->toContain($followed->id);
        expect($targetIds)->not->toContain($follower->id);
    });

    it('sends a notification even if the user had previously unfollowed and re-follows', function () {
        $follower = bob($this);
        $followed = alice($this);

        $this->actingAs($follower)->post(route('follow.follow', $followed->id));
        $this->actingAs($follower)->delete(route('follow.unfollow', $followed->id));
        $this->actingAs($follower)->post(route('follow.follow', $followed->id));

        $notifications = getAllNotificationsByKey(NewFollowerNotification::type());
        expect($notifications)->toHaveCount(2);
    });

    it('does not send a notification when following oneself', function () {
        $user = alice($this);

        $this->actingAs($user)->post(route('follow.follow', $user->id));

        $this->assertDatabaseCount('notifications', 0);
    });
});
