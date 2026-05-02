<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Follow\Private\Notifications\NewStoryNotification;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use App\Domains\Story\Public\Events\DTO\StorySnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('New story notification — StoryCreated', function () {
    it('notifies followers when author publishes a public story', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = publicStory('My Story', $author->id);
        $snapshot = StorySnapshot::fromModel($story, $author->id);

        app(EventBus::class)->emit(new StoryCreated($snapshot));

        $notification = getLatestNotificationByKey(NewStoryNotification::type());
        expect($notification)->not->toBeNull();
        expect(getNotificationTargetUserIds($notification->id))->toContain($follower->id);
    });

    it('notifies followers when author publishes a community story', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = communityStory('My Story', $author->id);
        $snapshot = StorySnapshot::fromModel($story, $author->id);

        app(EventBus::class)->emit(new StoryCreated($snapshot));

        $notification = getLatestNotificationByKey(NewStoryNotification::type());
        expect($notification)->not->toBeNull();
        expect(getNotificationTargetUserIds($notification->id))->toContain($follower->id);
    });

    it('does not notify followers when a private story is created', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = privateStory('My Draft', $author->id);
        $snapshot = StorySnapshot::fromModel($story, $author->id);

        app(EventBus::class)->emit(new StoryCreated($snapshot));

        $this->assertDatabaseCount('notifications', 0);
    });

    it('does not notify when there are no followers', function () {
        $author = alice($this);

        $story = publicStory('My Story', $author->id);
        $snapshot = StorySnapshot::fromModel($story, $author->id);

        app(EventBus::class)->emit(new StoryCreated($snapshot));

        $this->assertDatabaseCount('notifications', 0);
    });

    it('does not notify non-confirmed followers for a community story', function () {
        $author = alice($this);
        $unconfirmedFollower = bob($this, roles: [Roles::USER]);
        followUser($unconfirmedFollower->id, $author->id);

        $story = communityStory('My Story', $author->id);
        $snapshot = StorySnapshot::fromModel($story, $author->id);

        app(EventBus::class)->emit(new StoryCreated($snapshot));

        $this->assertDatabaseCount('notifications', 0);
    });

    it('notifies confirmed followers but not unconfirmed ones for a community story', function () {
        $author = alice($this);
        $confirmedFollower = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmedFollower = registerUserThroughForm($this, ['name' => 'Charlie', 'email' => 'charlie@example.com'], roles: [Roles::USER]);
        followUser($confirmedFollower->id, $author->id);
        followUser($unconfirmedFollower->id, $author->id);

        $story = communityStory('My Story', $author->id);
        $snapshot = StorySnapshot::fromModel($story, $author->id);

        app(EventBus::class)->emit(new StoryCreated($snapshot));

        $notification = getLatestNotificationByKey(NewStoryNotification::type());
        expect($notification)->not->toBeNull();
        $targetIds = getNotificationTargetUserIds($notification->id);
        expect($targetIds)->toContain($confirmedFollower->id);
        expect($targetIds)->not->toContain($unconfirmedFollower->id);
    });
});

describe('New story notification — StoryVisibilityChanged', function () {
    it('notifies followers when visibility changes from private to public', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = publicStory('My Story', $author->id);

        app(EventBus::class)->emit(new StoryVisibilityChanged(
            storyId: $story->id,
            title: $story->title,
            oldVisibility: 'private',
            newVisibility: 'public',
        ));

        $notification = getLatestNotificationByKey(NewStoryNotification::type());
        expect($notification)->not->toBeNull();
        expect(getNotificationTargetUserIds($notification->id))->toContain($follower->id);
    });

    it('notifies followers when visibility changes from private to community', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = communityStory('My Story', $author->id);

        app(EventBus::class)->emit(new StoryVisibilityChanged(
            storyId: $story->id,
            title: $story->title,
            oldVisibility: 'private',
            newVisibility: 'community',
        ));

        $notification = getLatestNotificationByKey(NewStoryNotification::type());
        expect($notification)->not->toBeNull();
    });

    it('does not notify when visibility changes from public to community (already published)', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = publicStory('My Story', $author->id);

        app(EventBus::class)->emit(new StoryVisibilityChanged(
            storyId: $story->id,
            title: $story->title,
            oldVisibility: 'public',
            newVisibility: 'community',
        ));

        $this->assertDatabaseCount('notifications', 0);
    });

    it('does not notify when visibility changes to private', function () {
        $author = alice($this);
        $follower = bob($this);
        followUser($follower->id, $author->id);

        $story = privateStory('My Draft', $author->id);

        app(EventBus::class)->emit(new StoryVisibilityChanged(
            storyId: $story->id,
            title: $story->title,
            oldVisibility: 'public',
            newVisibility: 'private',
        ));

        $this->assertDatabaseCount('notifications', 0);
    });
});
