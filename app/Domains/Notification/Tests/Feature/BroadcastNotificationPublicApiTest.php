<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Broadcast Notification public API', function () {
    describe('success cases', function () {
        it('broadcast creates notifications for all eligible users and increments their unread counts', function () {
            $alice = alice($this); // user-confirmed
            $bob = bob($this);     // user-confirmed

            /** @var NotificationPublicApi $api */
            $api = app(NotificationPublicApi::class);

            // Register test notification type
            $factory = app(\App\Domains\Notification\Public\Services\NotificationFactory::class);
            $factory->register(TestNotificationContent::type(), TestNotificationContent::class);

            // Precondition
            expect($api->getUnreadCount($alice->id))->toBe(0);
            expect($api->getUnreadCount($bob->id))->toBe(0);

            // Broadcast (should target roles: user and user-confirmed)
            $api->createBroadcastNotification(new TestNotificationContent(), $alice->id);

            // Expectation
            expect($api->getUnreadCount($alice->id))->toBe(1);
            expect($api->getUnreadCount($bob->id))->toBe(1);
        });
    });
});
