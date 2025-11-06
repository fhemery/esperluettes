<?php

use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Notification on chapter published', function () {
    it('sends notification to all users having the story in their readlist when ChapterPublished is emitted', function () {
        $author = alice($this);
        $story = publicStory('Notif Story', $author->id);

        // Two readers add story to readlist
        $r1 = bob($this);
        $r2 = carol($this);
        $this->actingAs($r1);
        addToReadList($this, $story->id);
        $this->actingAs($r2);
        addToReadList($this, $story->id);

        // Dispatch ChapterPublished event
        $snapshot = new ChapterSnapshot(
            id: 10,
            title: 'New Chapter',
            slug: 'new-chapter',
            sortOrder: 1,
            status: 'published',
            wordCount: 100,
            charCount: 500,
        );
        dispatchEvent(new ChapterPublished(storyId: $story->id, chapter: $snapshot));

        // Assert notification created with correct recipients
        $notif = getLatestNotificationByKey('readlist.chapter.published');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($r1->id);
        expect($targets)->toContain($r2->id);
        expect($targets)->toHaveCount(2);
    });

    it('sends notification when ChapterCreated is emitted with status published', function () {
        $author = alice($this);
        $story = publicStory('Notif Story 2', $author->id);
        $r1 = bob($this);
        $this->actingAs($r1);
        addToReadList($this, $story->id);

        $snapshot = new ChapterSnapshot(
            id: 11,
            title: 'Brand New',
            slug: 'brand-new',
            sortOrder: 2,
            status: 'published',
            wordCount: 200,
            charCount: 900,
        );
        dispatchEvent(new ChapterCreated(storyId: $story->id, chapter: $snapshot));

        $notif = getLatestNotificationByKey('readlist.chapter.published');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($r1->id);
        expect($targets)->toHaveCount(1);
    });

    it('does not send Notification for ChapterCreated with status draft', function () {
        $author = alice($this);
        $story = publicStory('Notif Story 3', $author->id);
        $r1 = bob($this);
        $this->actingAs($r1);
        addToReadList($this, $story->id);

        $snapshot = new ChapterSnapshot(
            id: 12,
            title: 'Draft Chapter',
            slug: 'draft-chapter',
            sortOrder: 3,
            status: 'draft',
            wordCount: 300,
            charCount: 1500,
        );
        dispatchEvent(new ChapterCreated(storyId: $story->id, chapter: $snapshot));

        expect(getLatestNotificationByKey('readlist.chapter.published'))->toBeNull();
    });

    it('notifies only readers who have access (e.g. collaborators for private stories)', function () {
        $author = alice($this);
        $story = publicStory('Private Notif', $author->id);

        // Two readers add story to readlist
        $collaborator = bob($this);   // will be collaborator → should receive notif
        $reader = carol($this); // not collaborator → should NOT receive notif
        addCollaborator($story->id, $collaborator->id, 'Collab');
        
        $this->actingAs($collaborator);
        addToReadList($this, $story->id);
        $this->actingAs($reader);
        addToReadList($this, $story->id);

        setStoryVisibility($story->id, 'private');

        // Dispatch ChapterPublished event
        $snapshot = new ChapterSnapshot(
            id: 21,
            title: 'Secret Chapter',
            slug: 'secret-chapter',
            sortOrder: 1,
            status: 'published',
            wordCount: 100,
            charCount: 500,
        );
        dispatchEvent(new ChapterPublished(storyId: $story->id, chapter: $snapshot));

        // Assert only collaborator reader (r1) is notified
        $notif = getLatestNotificationByKey('readlist.chapter.published');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($collaborator->id);
        expect($targets)->not->toContain($reader->id);
        expect($targets)->toHaveCount(1);
    });
});
