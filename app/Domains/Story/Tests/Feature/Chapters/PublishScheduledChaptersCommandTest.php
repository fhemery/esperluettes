<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('story:publish-scheduled-chapters command', function () {

    it('publishes an unpublished chapter whose publish_at has been reached', function () {
        $author = alice($this);
        $story = publicStory('Sched Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Due Chapter']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');

        $chapter->refresh();
        expect($chapter->status)->toBe(Chapter::STATUS_PUBLISHED)
            ->and($chapter->publish_at)->toBeNull()
            ->and($chapter->first_published_at)->not->toBeNull();
    });

    it('updates story last_chapter_published_at when auto-publishing', function () {
        $author = alice($this);
        $story = publicStory('Sched Story2', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Story TS Ch']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');

        $story->refresh();
        expect($story->last_chapter_published_at)->not->toBeNull();
    });

    it('does not publish chapters with publish_at in the future', function () {
        $author = alice($this);
        $story = publicStory('Future Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Future Ch']);

        $chapter->publish_at = now()->addHour();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');

        $chapter->refresh();
        expect($chapter->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
    });

    it('does not publish plain draft chapters with no publish_at', function () {
        $author = alice($this);
        $story = publicStory('Draft Story', $author->id);
        createUnpublishedChapter($this, $story, $author, ['title' => 'Plain Draft']);

        Artisan::call('story:publish-scheduled-chapters');

        $chapter = Chapter::query()->firstOrFail();
        expect($chapter->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
    });

    it('does not attempt to re-publish an already-published chapter', function () {
        $author = alice($this);
        $story = publicStory('Already Pub Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Already Published']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        $initialPublishedAt = $chapter->first_published_at;

        Artisan::call('story:publish-scheduled-chapters');

        $chapter->refresh();
        expect($chapter->status)->toBe(Chapter::STATUS_PUBLISHED)
            ->and($chapter->first_published_at->eq($initialPublishedAt))->toBeTrue();
    });

    it('emits ChapterPublished event when auto-publishing', function () {
        $author = alice($this);
        $story = publicStory('Event Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Event Ch']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');

        $event = latestEventOf(ChapterPublished::name(), ChapterPublished::class);
        expect($event)->not->toBeNull();
        expect($event->chapter->id)->toBe($chapter->id);
        expect($event->chapter->status)->toBe(Chapter::STATUS_PUBLISHED);
    });

    it('emits ChapterUpdated event when auto-publishing', function () {
        $author = alice($this);
        $story = publicStory('Updated Event Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Updated Event Ch']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');

        $event = latestEventOf(ChapterUpdated::name(), ChapterUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->after->id)->toBe($chapter->id);
        expect($event->after->status)->toBe(Chapter::STATUS_PUBLISHED);
    });

    it('notifies the author when their chapter is auto-published', function () {
        $author = alice($this);
        $story = publicStory('Author Notif Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Author Notif Ch']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        /** @var NotificationPublicApi $notifApi */
        $notifApi = app(NotificationPublicApi::class);
        expect($notifApi->getUnreadCount($author->id))->toBe(0);

        Artisan::call('story:publish-scheduled-chapters');

        expect($notifApi->getUnreadCount($author->id))->toBe(1);

        $notif = getLatestNotificationByKey('story.chapter.scheduled_published');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($author->id);
    });

    it('notifies readlist subscribers when a scheduled chapter is auto-published', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Readlist Notif Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Readlist Ch']);

        $this->actingAs($reader);
        addToReadList($this, $story->id);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');

        $notif = getLatestNotificationByKey('readlist.chapter.published');
        expect($notif)->not->toBeNull();
        $targets = getNotificationTargetUserIds((int) $notif->id);
        expect($targets)->toContain($reader->id);
    });

    it('handles multiple scheduled chapters in one run', function () {
        $author = alice($this);
        $story = publicStory('Multi Story', $author->id);

        $past1 = createUnpublishedChapter($this, $story, $author, ['title' => 'Past 1']);
        $past1->publish_at = now()->subMinutes(2);
        $past1->save();

        $past2 = createUnpublishedChapter($this, $story, $author, ['title' => 'Past 2']);
        $past2->publish_at = now()->subMinutes(1);
        $past2->save();

        $future = createUnpublishedChapter($this, $story, $author, ['title' => 'Future Ch']);
        $future->publish_at = now()->addHour();
        $future->save();

        Artisan::call('story:publish-scheduled-chapters');

        expect($past1->refresh()->status)->toBe(Chapter::STATUS_PUBLISHED);
        expect($past2->refresh()->status)->toBe(Chapter::STATUS_PUBLISHED);
        expect($future->refresh()->status)->toBe(Chapter::STATUS_NOT_PUBLISHED);
    });

    it('is idempotent — running twice does not double-notify', function () {
        $author = alice($this);
        $story = publicStory('Idempotent Story', $author->id);
        $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Idempotent Ch']);

        $chapter->publish_at = now()->subMinute();
        $chapter->save();

        Artisan::call('story:publish-scheduled-chapters');
        Artisan::call('story:publish-scheduled-chapters');

        expect(countNotificationsByKey('story.chapter.scheduled_published'))->toBe(1);
    });

    it('outputs the count of published chapters', function () {
        $author = alice($this);
        $story = publicStory('Output Story', $author->id);
        $ch1 = createUnpublishedChapter($this, $story, $author, ['title' => 'Out Ch 1']);
        $ch1->publish_at = now()->subMinute();
        $ch1->save();

        Artisan::call('story:publish-scheduled-chapters');
        $output = Artisan::output();

        expect($output)->toContain('1');
    });
});
