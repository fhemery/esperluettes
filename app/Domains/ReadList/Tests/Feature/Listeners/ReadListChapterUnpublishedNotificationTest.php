<?php

use App\Domains\Story\Public\Events\ChapterUnpublished;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Notification on chapter unpublication/deletion', function () {
    it('sends notification to readlist users when ChapterUnpublished is emitted', function () {
        $author = alice($this);
        $story = publicStory('Unpub Story', $author->id);

        $r1 = bob($this);
        $this->actingAs($r1);
        addToReadList($this, $story->id);

        $snapshot = new ChapterSnapshot(
            id: 31,
            title: 'Was Published',
            slug: 'was-published',
            sortOrder: 4,
            status: 'unpublished',
            wordCount: 123,
            charCount: 456,
        );
        dispatchEvent(new ChapterUnpublished(storyId: $story->id, chapter: $snapshot));

        $notif = getLatestNotificationByKey('readlist.chapter.unpublished');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($r1->id);
        expect($targets)->toHaveCount(1);
    });

    it('sends notification to readlist users when ChapterDeleted is emitted', function () {
        $author = alice($this);
        $story = publicStory('Deleted Story', $author->id);

        $r = bob($this);
        $this->actingAs($r);
        addToReadList($this, $story->id);

        $snapshot = new ChapterSnapshot(
            id: 33,
            title: 'To Delete',
            slug: 'to-delete',
            sortOrder: 6,
            status: 'published',
            wordCount: 50,
            charCount: 300,
        );
        dispatchEvent(new ChapterDeleted(storyId: $story->id, chapter: $snapshot));

        $notif = getLatestNotificationByKey('readlist.chapter.unpublished');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($r->id);
        expect($targets)->toHaveCount(1);
    });

    it('filters recipients when story changed from public to community (USER no longer notified)', function () {
        $author = alice($this);
        $story = publicStory('Visibility Change', $author->id);

        // One confirmed and one unconfirmed reader add story to readlist
        $confirmed = bob($this, roles: [Roles::USER_CONFIRMED]);
        $unconfirmed = carol($this, roles: [Roles::USER]);
        $this->actingAs($confirmed);
        addToReadList($this, $story->id);
        $this->actingAs($unconfirmed);
        addToReadList($this, $story->id);

        // Change story visibility from public to community
        setStoryVisibility($story->id, 'community');

        // Dispatch ChapterUnpublished event
        $snapshot = new ChapterSnapshot(
            id: 41,
            title: 'Now Hidden',
            slug: 'now-hidden',
            sortOrder: 7,
            status: 'unpublished',
            wordCount: 10,
            charCount: 50,
        );
        dispatchEvent(new ChapterUnpublished(storyId: $story->id, chapter: $snapshot));

        // Only confirmed should be notified
        $notif = getLatestNotificationByKey('readlist.chapter.unpublished');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($confirmed->id);
        expect($targets)->not->toContain($unconfirmed->id);
        expect($targets)->toHaveCount(1);
    });
});
