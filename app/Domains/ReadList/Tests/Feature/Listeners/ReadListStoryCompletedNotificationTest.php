<?php

use App\Domains\Story\Public\Events\StoryUpdated;
use App\Domains\Story\Public\Events\DTO\StorySnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('ReadList notifications on StoryUpdated (completion)', function () {
    it('notifies readers when a story in their readlist is marked complete', function () {
        $author = alice($this);
        $story = publicStory('Completion Tale', $author->id);

        $r1 = bob($this);
        $r2 = carol($this);
        $this->actingAs($r1);
        addToReadList($this, $story->id);
        $this->actingAs($r2);
        addToReadList($this, $story->id);

        $before = new StorySnapshot(
            storyId: $story->id,
            createdByUserId: $author->id,
            title: $story->title,
            slug: $story->slug,
            visibility: $story->visibility,
            summaryWordCount: 0,
            summaryCharCount: 0,
            typeId: $story->story_ref_type_id,
            audienceId: $story->story_ref_audience_id,
            copyrightId: $story->story_ref_copyright_id,
            statusId: $story->story_ref_status_id,
            feedbackId: $story->story_ref_feedback_id,
            genreIds: [],
            triggerWarningIds: [],
            isComplete: false,
            isExcludedFromEvents: null,
        );

        $after = new StorySnapshot(
            storyId: $story->id,
            createdByUserId: $author->id,
            title: $story->title,
            slug: $story->slug,
            visibility: $story->visibility,
            summaryWordCount: 0,
            summaryCharCount: 0,
            typeId: $story->story_ref_type_id,
            audienceId: $story->story_ref_audience_id,
            copyrightId: $story->story_ref_copyright_id,
            statusId: $story->story_ref_status_id,
            feedbackId: $story->story_ref_feedback_id,
            genreIds: [],
            triggerWarningIds: [],
            isComplete: true,
            isExcludedFromEvents: null,
        );

        dispatchEvent(new StoryUpdated($before, $after));

        $notif = getLatestNotificationByKey('readlist.story.completed');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($r1->id);
        expect($targets)->toContain($r2->id);
        expect($targets)->toHaveCount(2);
    });

    it('does not notify when story was already complete', function () {
        $author = alice($this);
        $story = publicStory('Already Complete', $author->id);

        $reader = bob($this);
        $this->actingAs($reader);
        addToReadList($this, $story->id);

        $before = new StorySnapshot(
            storyId: $story->id,
            createdByUserId: $author->id,
            title: $story->title,
            slug: $story->slug,
            visibility: $story->visibility,
            summaryWordCount: 0,
            summaryCharCount: 0,
            typeId: $story->story_ref_type_id,
            audienceId: $story->story_ref_audience_id,
            copyrightId: $story->story_ref_copyright_id,
            statusId: $story->story_ref_status_id,
            feedbackId: $story->story_ref_feedback_id,
            genreIds: [],
            triggerWarningIds: [],
            isComplete: true,
            isExcludedFromEvents: null,
        );

        $after = new StorySnapshot(
            storyId: $story->id,
            createdByUserId: $author->id,
            title: $story->title,
            slug: $story->slug,
            visibility: $story->visibility,
            summaryWordCount: 0,
            summaryCharCount: 0,
            typeId: $story->story_ref_type_id,
            audienceId: $story->story_ref_audience_id,
            copyrightId: $story->story_ref_copyright_id,
            statusId: $story->story_ref_status_id,
            feedbackId: $story->story_ref_feedback_id,
            genreIds: [],
            triggerWarningIds: [],
            isComplete: true,
            isExcludedFromEvents: null,
        );

        dispatchEvent(new StoryUpdated($before, $after));

        expect(getLatestNotificationByKey('readlist.story.completed'))->toBeNull();
    });
});
