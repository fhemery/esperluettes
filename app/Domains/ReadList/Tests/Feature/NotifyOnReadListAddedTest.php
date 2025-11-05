<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('RL-011 Notifications on ReadList add', function () {
    beforeEach(function () {
       Cache::flush();
    });

    it('notifies all authors when a reader adds a story to their Read List', function () {
        $author = alice($this);
        $story = publicStory('Story A', $author->id);

        $reader = bob($this);
        $this->actingAs($reader);
        addToReadList($this, $story->id)->assertRedirect();

        // Assert a notification of expected type was created
        $notif = getLatestNotificationByKey('readlist.story.added');
        expect($notif)->not->toBeNull();

        // Assert a notification_read was created for the author
        $reads = getNotificationTargetUserIds((int) $notif->id);

        expect($reads)->toContain($author->id);
        expect($reads)->toHaveCount(1);
    });

    it('does not notify when the reader is an author (robustness)', function () {
        $author = alice($this);
        $story = publicStory('Story B', $author->id);

        $this->actingAs($author);
        $this->post(route('readlist.add', $story->id))->assertForbidden();

        $count = countNotificationsByKey('readlist.story.added');
        expect($count)->toBe(0);
    });

    it('does not duplicate notification when story is already in Read List', function () {
        $author = alice($this);
        $story = publicStory('Story C', $author->id);

        $reader = bob($this);
        $this->actingAs($reader);

        addToReadList($this, $story->id)->assertRedirect();
        addToReadList($this, $story->id)->assertRedirect();

        $count = countNotificationsByKey('readlist.story.added');
        expect($count)->toBe(1);

        $notif = getLatestNotificationByKey('readlist.story.added');
        $reads = getNotificationTargetUserIds((int) $notif->id);
        expect(count($reads))->toBe(1);
    });

    it('notifies all co-authors when present', function () {
        $author1 = alice($this);
        $author2 = carol($this);
        $story = publicStory('Story D', $author1->id);

        // Attach second author on pivot
        DB::table('story_collaborators')->insert([
            'story_id' => $story->id,
            'user_id' => $author2->id,
            'role' => 'author',
            'invited_by_user_id' => $author1->id,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $reader = bob($this);
        $this->actingAs($reader);
        addToReadList($this, $story->id)->assertRedirect();

        $notif = getLatestNotificationByKey('readlist.story.added');
        expect($notif)->not->toBeNull();

        $reads = getNotificationTargetUserIds((int) $notif->id);

        expect($reads)->toContain($author1->id);
        expect($reads)->toContain($author2->id);
        expect($reads)->toHaveCount(2);
    });
});
