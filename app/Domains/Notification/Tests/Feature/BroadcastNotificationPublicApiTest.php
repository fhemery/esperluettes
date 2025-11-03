<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Broadcast Notification public API', function () {
    describe('error cases', function () {
        it('rejects when contentKey is empty or whitespace', function (string $badKey) {
            $alice = alice($this);

            /** @var NotificationPublicApi $api */
            $api = app(NotificationPublicApi::class);

            expect(fn () => $api->createBroadcastNotification($badKey, [
                'title' => 'Hello',
                'slug' => 'hello',
            ], $alice->id))->toThrow(function (ValidationException $e) {
                $errors = $e->errors();
                expect($errors)->toHaveKey('contentKey');
                expect($errors['contentKey'][0])->toBe(trans('notifications::validation.content_key_required'));
            });
        })->with([
            '',
            '   ',
        ]);
    });

    describe('success cases', function () {
        it('broadcast creates notifications for all eligible users and increments their unread counts', function () {
            $alice = alice($this); // user-confirmed
            $bob = bob($this);     // user-confirmed

            /** @var NotificationPublicApi $api */
            $api = app(NotificationPublicApi::class);

            // Precondition
            expect($api->getUnreadCount($alice->id))->toBe(0);
            expect($api->getUnreadCount($bob->id))->toBe(0);

            // Broadcast (should target roles: user and user-confirmed)
            $api->createBroadcastNotification('news::notification.posted', [
                'title' => 'Hello',
                'slug' => 'hello',
            ], $alice->id);

            // Expectation
            expect($api->getUnreadCount($alice->id))->toBe(1);
            expect($api->getUnreadCount($bob->id))->toBe(1);
        });
    });
});
