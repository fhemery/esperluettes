<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Public\Notifications\CollaboratorRoleGivenNotification;
use App\Domains\Story\Public\Notifications\CollaboratorRemovedNotification;
use App\Domains\Story\Public\Notifications\CollaboratorLeftNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Collaborator notifications', function () {

    describe('When adding a collaborator', function () {
        it('notifies the target user when added as beta-reader', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $betaReader = bob($this, roles: [Roles::USER]);
            $story = privateStory('My Story', $author->id);

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            // Verify no notifications before
            expect($notifApi->getUnreadCount($betaReader->id))->toBe(0);

            // Add beta-reader
            $this->actingAs($author);
            $this->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$betaReader->id],
                'role' => 'beta-reader',
            ]);

            // Verify notification was sent
            expect($notifApi->getUnreadCount($betaReader->id))->toBe(1);
        });

        it('notifies the target user when added as co-author', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $coAuthor = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = privateStory('My Story', $author->id);

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($coAuthor->id))->toBe(0);

            $this->actingAs($author);
            $this->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$coAuthor->id],
                'role' => 'author',
            ]);

            expect($notifApi->getUnreadCount($coAuthor->id))->toBe(1);
        });

        it('notifies the target user when upgraded from beta-reader to co-author', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $user = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = privateStory('My Story', $author->id);
            addCollaborator($story->id, $user->id, 'beta-reader');

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            // Clear any existing notifications
            $initialCount = $notifApi->getUnreadCount($user->id);

            $this->actingAs($author);
            $this->post(route('stories.collaborators.store', ['slug' => $story->slug]), [
                'target_users' => [$user->id],
                'role' => 'author',
            ]);

            expect($notifApi->getUnreadCount($user->id))->toBe($initialCount + 1);
        });
    });

    describe('When removing a collaborator', function () {
        it('notifies the removed beta-reader', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $betaReader = bob($this, roles: [Roles::USER]);
            $story = privateStory('My Story', $author->id);
            addCollaborator($story->id, $betaReader->id, 'beta-reader');

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($betaReader->id))->toBe(0);

            $this->actingAs($author);
            $this->delete(route('stories.collaborators.destroy', [
                'slug' => $story->slug,
                'targetUserId' => $betaReader->id,
            ]));

            expect($notifApi->getUnreadCount($betaReader->id))->toBe(1);
        });
    });

    describe('When a collaborator leaves', function () {
        it('notifies remaining authors when a co-author leaves', function () {
            $author1 = alice($this, roles: [Roles::USER_CONFIRMED]);
            $author2 = bob($this, roles: [Roles::USER_CONFIRMED]);
            $story = privateStory('My Story', $author1->id);
            addCollaborator($story->id, $author2->id, 'author');

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($author1->id))->toBe(0);

            $this->actingAs($author2);
            $this->post(route('stories.collaborators.leave', ['slug' => $story->slug]));

            expect($notifApi->getUnreadCount($author1->id))->toBe(1);
        });

        it('notifies remaining authors when a beta-reader leaves', function () {
            $author = alice($this, roles: [Roles::USER_CONFIRMED]);
            $betaReader = bob($this, roles: [Roles::USER]);
            $story = privateStory('My Story', $author->id);
            addCollaborator($story->id, $betaReader->id, 'beta-reader');

            /** @var NotificationPublicApi $notifApi */
            $notifApi = app(NotificationPublicApi::class);

            expect($notifApi->getUnreadCount($author->id))->toBe(0);

            $this->actingAs($betaReader);
            $this->post(route('stories.collaborators.leave', ['slug' => $story->slug]));

            expect($notifApi->getUnreadCount($author->id))->toBe(1);
        });
    });
});
