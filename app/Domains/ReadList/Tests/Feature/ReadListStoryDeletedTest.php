<?php

use App\Domains\Story\Public\Events\StoryDeleted;
use App\Domains\Story\Public\Events\DTO\StorySnapshot;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('Readlist on story deletion', function () {
    it('sends notification to readlist users when StoryDeleted is emitted', function () {
        $author = alice($this);
        $story = publicStory('Story To Delete', $author->id);

        $r1 = bob($this);
        $r2 = carol($this);

        $this->actingAs($r1);
        addToReadList($this, $story->id);
        $this->actingAs($r2);
        addToReadList($this, $story->id);

        $snapshot = StorySnapshot::fromModel(getStory($story->id), $author->id);
        dispatchEvent(new StoryDeleted(story: $snapshot, chapters: []));

        $notif = getLatestNotificationByKey('readlist.story.deleted');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($r1->id);
        expect($targets)->toContain($r2->id);
        expect($targets)->toHaveCount(2);
    });

    it('filters recipients when story is community: only user-confirmed notified', function () {
        $author = alice($this);
        $story = publicStory('Community Only', $author->id);

        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        $this->actingAs($confirmed);
        addToReadList($this, $story->id);
        $this->actingAs($unconfirmed);
        addToReadList($this, $story->id);

        setStoryVisibility($story->id, 'community');

        $snapshot = StorySnapshot::fromModel(getStory($story->id), $author->id);
        dispatchEvent(new StoryDeleted(story: $snapshot, chapters: []));

        $notif = getLatestNotificationByKey('readlist.story.deleted');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($confirmed->id);
        expect($targets)->not->toContain($unconfirmed->id);
        expect($targets)->toHaveCount(1);
    });

    it('deletes all readlist entries for the story when StoryDeleted is emitted', function () {
        $author = alice($this);
        $story = publicStory('Cleanup Readlist', $author->id);

        $u1 = bob($this);
        $u2 = carol($this);
        $this->actingAs($u1);
        addToReadList($this, $story->id);
        $this->actingAs($u2);
        addToReadList($this, $story->id);

        // Sanity: entries exist
        $this->assertDatabaseHas('read_list_entries', ['user_id' => $u1->id, 'story_id' => $story->id]);
        $this->assertDatabaseHas('read_list_entries', ['user_id' => $u2->id, 'story_id' => $story->id]);

        $snapshot = StorySnapshot::fromModel(getStory($story->id), $author->id);
        dispatchEvent(new StoryDeleted(story: $snapshot, chapters: []));

        // Entries should be gone
        $this->assertDatabaseMissing('read_list_entries', ['user_id' => $u1->id, 'story_id' => $story->id]);
        $this->assertDatabaseMissing('read_list_entries', ['user_id' => $u2->id, 'story_id' => $story->id]);
    });
});
