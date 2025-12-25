<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Chapter co-author notifications', function () {

    describe('When creating a chapter', function () {
        it('notifies co-authors when a chapter is created', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($author2->id))->toBe(0);

            // Author1 creates a chapter
            createPublishedChapter($this, $story, $author1, ['title' => 'New Chapter']);

            // Author2 should be notified
            expect($notifApi->getUnreadCount($author2->id))->toBe(1);
        });

        it('does not notify the author who created the chapter', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($author1->id))->toBe(0);

            // Author1 creates a chapter
            createPublishedChapter($this, $story, $author1, ['title' => 'New Chapter']);

            // Author1 should NOT be notified (they created it)
            expect($notifApi->getUnreadCount($author1->id))->toBe(0);
        });

        it('does not send notifications when there is only one author', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author->id);

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($author->id))->toBe(0);

            createPublishedChapter($this, $story, $author, ['title' => 'New Chapter']);

            // No notifications should be sent
            expect($notifApi->getUnreadCount($author->id))->toBe(0);
        });
    });

    describe('When updating a chapter', function () {
        it('notifies co-authors when a chapter is updated', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            $chapter = createPublishedChapter($this, $story, $author1, ['title' => 'Original Title']);

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            // Clear notification from chapter creation
            $initialCount = $notifApi->getUnreadCount($author2->id);

            // Author1 updates the chapter
            $this->actingAs($author1);
            $this->put(route('chapters.update', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]), [
                'title' => 'Updated Title',
                'content' => '<p>Updated content</p>',
            ]);

            // Author2 should be notified
            expect($notifApi->getUnreadCount($author2->id))->toBe($initialCount + 1);
        });
    });

    describe('When deleting a chapter', function () {
        it('notifies co-authors when a chapter is deleted', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = publicStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            $chapter = createPublishedChapter($this, $story, $author1, ['title' => 'To Delete']);

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            // Clear notification from chapter creation
            $initialCount = $notifApi->getUnreadCount($author2->id);

            // Author1 deletes the chapter
            $this->actingAs($author1);
            $this->delete(route('chapters.destroy', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]));

            // Author2 should be notified
            expect($notifApi->getUnreadCount($author2->id))->toBe($initialCount + 1);
        });
    });
});
