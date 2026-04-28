<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $factory = new NotificationFactory();
    $factory->registerGroup('test', 10, 'test.group');
    $factory->register(
        type: TestNotificationContent::type(),
        class: TestNotificationContent::class,
        groupId: 'test',
        nameKey: 'test.normal',
    );
    app()->instance(NotificationFactory::class, $factory);
});

describe('getNotificationsByIds', function () {
    it('returns an empty array for an empty input', function () {
        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);

        expect($api->getNotificationsByIds([]))->toBe([]);
    });

    it('returns a NotificationDto keyed by notification ID', function () {
        $alice = alice($this);
        $id = makeNotification([$alice->id], new TestNotificationContent('hello world'), $alice->id);

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);

        $result = $api->getNotificationsByIds([$id]);

        expect($result)->toHaveKey($id);
        $dto = $result[$id];
        expect($dto)->toBeInstanceOf(NotificationDto::class);
        expect($dto->id)->toBe($id);
        expect($dto->type)->toBe(TestNotificationContent::type());
        expect($dto->data)->toBe(['message' => 'hello world']);
        expect($dto->htmlDisplay)->toBe('hello world');
    });

    it('populates sourceUserId from the notification record', function () {
        $alice = alice($this);
        $bob   = bob($this);
        $id    = makeNotification([$alice->id], new TestNotificationContent(), $bob->id);

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);

        $result = $api->getNotificationsByIds([$id]);

        expect($result[$id]->sourceUserId)->toBe($bob->id);
    });

    it('returns null sourceUserId when none was set', function () {
        $alice = alice($this);

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);

        // Call directly so sourceUserId stays null (makeNotification() defaults it to $userIds[0])
        $api->createNotification([$alice->id], new TestNotificationContent(), null);
        $id = (int) \Illuminate\Support\Facades\DB::table('notifications')->orderByDesc('id')->value('id');

        $result = $api->getNotificationsByIds([$id]);

        expect($result[$id]->sourceUserId)->toBeNull();
    });

    it('silently omits unknown IDs', function () {
        $alice  = alice($this);
        $id     = makeNotification([$alice->id]);
        $unknownId = 999999;

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);

        $result = $api->getNotificationsByIds([$id, $unknownId]);

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey($id);
        expect($result)->not->toHaveKey($unknownId);
    });

    it('returns multiple DTOs when given multiple valid IDs', function () {
        $alice  = alice($this);
        $id1    = makeNotification([$alice->id], new TestNotificationContent('first'));
        $id2    = makeNotification([$alice->id], new TestNotificationContent('second'));

        /** @var NotificationPublicApi $api */
        $api = app(NotificationPublicApi::class);

        $result = $api->getNotificationsByIds([$id1, $id2]);

        expect($result)->toHaveCount(2);
        expect($result[$id1]->htmlDisplay)->toBe('first');
        expect($result[$id2]->htmlDisplay)->toBe('second');
    });
});
