<?php

use App\Domains\Story\Public\Notifications\ChapterCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The legacy ChapterCommentNotification (type: story.chapter.comment) must continue
 * to display correctly for the 30-day window until the cleanup job removes old records.
 */
describe('ChapterCommentNotification legacy display', function () {
    it('renders a root comment notification without errors', function () {
        $notification = new ChapterCommentNotification(
            commentId: 42,
            authorName: 'Alice',
            authorSlug: 'alice',
            chapterTitle: 'Chapter One',
            storySlug: 'my-story',
            chapterSlug: 'chapter-one',
            isReply: false,
            storyName: 'My Story',
        );

        $html = $notification->display();

        expect($html)->toBeString()->not->toBeEmpty();
    });

    it('renders a reply comment notification without errors', function () {
        $notification = new ChapterCommentNotification(
            commentId: 43,
            authorName: 'Bob',
            authorSlug: 'bob',
            chapterTitle: 'Chapter Two',
            storySlug: 'my-story',
            chapterSlug: 'chapter-two',
            isReply: true,
            storyName: 'My Story',
        );

        $html = $notification->display();

        expect($html)->toBeString()->not->toBeEmpty();
    });

    it('can be reconstructed from stored data', function () {
        $original = new ChapterCommentNotification(
            commentId: 1,
            authorName: 'Carol',
            authorSlug: 'carol',
            chapterTitle: 'Ch 1',
            storySlug: 'story-a',
            chapterSlug: 'ch-1',
            isReply: true,
            storyName: 'Story A',
        );

        $reconstructed = ChapterCommentNotification::fromData($original->toData());

        expect($reconstructed->commentId)->toBe(1);
        expect($reconstructed->authorName)->toBe('Carol');
        expect($reconstructed->isReply)->toBe(true);
        expect($reconstructed->display())->toEqual($original->display());
    });
});
