<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Create Notification public API', function () {
    describe('error cases', function () {
        it('rejects when some target users do not exist', function () {
            $alice = alice($this);
            $bob = bob($this);

            /** @var NotificationPublicApi $api */
            $api = app(NotificationPublicApi::class);

            $nonExistingId = 999999;

            expect(fn() => $api->createNotification([
                $alice->id,
                $bob->id,
                $nonExistingId,
            ], new TestNotificationContent(), $alice->id))->toThrow(function (ValidationException $e) {
                $errors = $e->errors();
                expect($errors)->toHaveKey('userIds');
                expect($errors['userIds'][0])->toBe(trans('notifications::validation.non_existing_users'));
            });
        });

        it('rejects when sourceUserId does not exist', function () {
            $alice = alice($this);

            /** @var NotificationPublicApi $api */
            $api = app(NotificationPublicApi::class);

            $nonExistingId = 888888;

            expect(fn() => $api->createNotification([
                $alice->id,
            ], new TestNotificationContent(), $nonExistingId))->toThrow(function (ValidationException $e) use ($nonExistingId) {
                $errors = $e->errors();
                expect($errors)->toHaveKey('sourceUserId');
                expect($errors['sourceUserId'][0])->toBe(trans('notifications::validation.invalid_source_user'));
            });
        });
    });

    describe('success cases', function () {
        it('creates a notification and the target user has unread count 1', function () {
            $alice = alice($this);

            /** @var NotificationPublicApi $api */
            $api = app(NotificationPublicApi::class);
            expect($api->getUnreadCount($alice->id))->toBe(0);

            $api->createNotification([
                $alice->id,
            ], new TestNotificationContent(), $alice->id);

            expect($api->getUnreadCount($alice->id))->toBe(1);
        });

    });
});
