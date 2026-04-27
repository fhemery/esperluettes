<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Notification\Tests\Fixtures\ForcedTestNotificationContent;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    $factory = new NotificationFactory();
    $factory->registerGroup('test', 10, 'test.group');
    $factory->register(
        type: TestNotificationContent::type(),
        class: TestNotificationContent::class,
        groupId: 'test',
        nameKey: 'test.normal',
    );
    $factory->register(
        type: ForcedTestNotificationContent::type(),
        class: ForcedTestNotificationContent::class,
        groupId: 'test',
        nameKey: 'test.forced',
        forcedOnWebsite: true,
    );
    app()->instance(NotificationFactory::class, $factory);
    app()->instance(NotificationChannelRegistry::class, new NotificationChannelRegistry());
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function api(): NotificationPublicApi
{
    return app(NotificationPublicApi::class);
}

/** Count notification_reads rows for a user. */
function readsCount(int $userId): int
{
    return DB::table('notification_reads')->where('user_id', $userId)->count();
}

/** Count rows in the notifications table. */
function notificationRecordsCount(): int
{
    return DB::table('notifications')->count();
}

/** Store an opt-out for a user on the website channel. */
function optOutWebsite(int $userId, string $type): void
{
    DB::table('notification_preferences')->insert([
        'user_id' => $userId,
        'type'    => $type,
        'channel' => 'website',
        'enabled' => false,
    ]);
}

/** Store an opt-in for a user on an external channel. */
function optInChannel(int $userId, string $type, string $channel): void
{
    DB::table('notification_preferences')->insert([
        'user_id' => $userId,
        'type'    => $type,
        'channel' => $channel,
        'enabled' => true,
    ]);
}

/**
 * Register a fake active channel and return an ArrayObject that is appended to on each callback invocation.
 * ArrayObject is passed by handle so the caller sees all appended entries.
 */
function registerFakeChannel(string $id = 'fake', bool $defaultEnabled = false, ?string $featureFlag = null): \ArrayObject
{
    $calls = new \ArrayObject([]);
    app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
        id: $id,
        nameTranslationKey: 'test::channel',
        defaultEnabled: $defaultEnabled,
        sortOrder: 1,
        deliveryCallback: function (NotificationDto $dto, array $userIds) use ($calls) {
            $calls->append(['dto' => $dto, 'userIds' => $userIds]);
        },
        featureFlag: $featureFlag,
    ));
    return $calls;
}

// ---------------------------------------------------------------------------
// createNotification — website channel
// ---------------------------------------------------------------------------

describe('createNotification — website channel', function () {
    it('delivers to all targeted users by default (no stored preferences)', function () {
        $alice = alice($this);
        $bob   = bob($this);

        api()->createNotification([$alice->id, $bob->id], new TestNotificationContent());

        expect(readsCount($alice->id))->toBe(1);
        expect(readsCount($bob->id))->toBe(1);
    });

    it('excludes a user who opted out of the website channel', function () {
        $alice = alice($this);
        $bob   = bob($this);

        optOutWebsite($alice->id, TestNotificationContent::type());

        api()->createNotification([$alice->id, $bob->id], new TestNotificationContent());

        expect(readsCount($alice->id))->toBe(0);
        expect(readsCount($bob->id))->toBe(1);
    });

    it('always delivers to a user who opted out if the type is forcedOnWebsite', function () {
        $alice = alice($this);

        optOutWebsite($alice->id, ForcedTestNotificationContent::type());

        api()->createNotification([$alice->id], new ForcedTestNotificationContent());

        expect(readsCount($alice->id))->toBe(1);
    });

    it('creates the notification record even when all users opted out (Option B)', function () {
        $alice = alice($this);
        optOutWebsite($alice->id, TestNotificationContent::type());

        api()->createNotification([$alice->id], new TestNotificationContent());

        expect(notificationRecordsCount())->toBe(1);
        expect(readsCount($alice->id))->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// createNotification — external channels
// ---------------------------------------------------------------------------

describe('createNotification — external channels', function () {
    it('calls the channel callback for opted-in users on a default-OFF channel', function () {
        $calls = registerFakeChannel('discord', defaultEnabled: false);
        $alice  = alice($this);
        $bob    = bob($this);

        optInChannel($alice->id, TestNotificationContent::type(), 'discord');

        api()->createNotification([$alice->id, $bob->id], new TestNotificationContent());

        expect($calls)->toHaveCount(1);
        expect($calls[0]['userIds'])->toBe([$alice->id]);
    });

    it('skips the channel callback when no user opted in on a default-OFF channel', function () {
        $calls = registerFakeChannel('discord', defaultEnabled: false);
        $alice  = alice($this);

        api()->createNotification([$alice->id], new TestNotificationContent());

        expect($calls)->toBeEmpty();
    });

    it('calls the channel callback for all targeted users on a default-ON channel', function () {
        $calls = registerFakeChannel('email', defaultEnabled: true);
        $alice  = alice($this);
        $bob    = bob($this);

        api()->createNotification([$alice->id, $bob->id], new TestNotificationContent());

        expect($calls)->toHaveCount(1);
        expect($calls[0]['userIds'])->toEqualCanonicalizing([$alice->id, $bob->id]);
    });

    it('excludes opted-out users from a default-ON channel callback', function () {
        $calls = registerFakeChannel('email', defaultEnabled: true);
        $alice  = alice($this);
        $bob    = bob($this);

        DB::table('notification_preferences')->insert([
            'user_id' => $alice->id,
            'type'    => TestNotificationContent::type(),
            'channel' => 'email',
            'enabled' => false,
        ]);

        api()->createNotification([$alice->id, $bob->id], new TestNotificationContent());

        expect($calls)->toHaveCount(1);
        expect($calls[0]['userIds'])->toBe([$bob->id]);
    });

    it('passes a correctly populated NotificationDto to the callback', function () {
        $calls = registerFakeChannel('discord', defaultEnabled: false);
        $alice  = alice($this);

        optInChannel($alice->id, TestNotificationContent::type(), 'discord');

        $content = new TestNotificationContent('hello world');
        api()->createNotification([$alice->id], $content);

        expect($calls)->toHaveCount(1);
        $dto = $calls[0]['dto'];
        expect($dto)->toBeInstanceOf(NotificationDto::class);
        expect($dto->type)->toBe(TestNotificationContent::type());
        expect($dto->data)->toBe(['message' => 'hello world']);
        expect($dto->id)->toBeGreaterThan(0);
        expect($dto->htmlDisplay)->toBe('hello world');
        expect($dto->sourceUserId)->toBeNull();
    });

    it('skips an inactive channel even when users have opted in', function () {
        $calls = registerFakeChannel('discord', defaultEnabled: false, featureFlag: 'services.discord.enabled');
        $alice  = alice($this);

        optInChannel($alice->id, TestNotificationContent::type(), 'discord');

        api()->createNotification([$alice->id], new TestNotificationContent());

        // Feature flag not set → channel inactive → callback never called
        expect($calls)->toBeEmpty();
    });

    it('does not call the callback when all users opted out of the default-ON channel', function () {
        $calls = registerFakeChannel('email', defaultEnabled: true);
        $alice  = alice($this);

        DB::table('notification_preferences')->insert([
            'user_id' => $alice->id,
            'type'    => TestNotificationContent::type(),
            'channel' => 'email',
            'enabled' => false,
        ]);

        api()->createNotification([$alice->id], new TestNotificationContent());

        expect($calls)->toBeEmpty();
    });
});

// ---------------------------------------------------------------------------
// createBroadcastNotification — website channel
// ---------------------------------------------------------------------------

describe('createBroadcastNotification — website channel', function () {
    it('delivers to all eligible users by default', function () {
        $alice = alice($this);
        $bob   = bob($this);

        api()->createBroadcastNotification(new TestNotificationContent());

        expect(readsCount($alice->id))->toBe(1);
        expect(readsCount($bob->id))->toBe(1);
    });

    it('excludes users who opted out from broadcast delivery', function () {
        $alice = alice($this);
        $bob   = bob($this);

        optOutWebsite($alice->id, TestNotificationContent::type());

        api()->createBroadcastNotification(new TestNotificationContent());

        expect(readsCount($alice->id))->toBe(0);
        expect(readsCount($bob->id))->toBe(1);
    });

    it('always creates the notification record even when all users opted out (Option B)', function () {
        $alice = alice($this);
        optOutWebsite($alice->id, TestNotificationContent::type());

        api()->createBroadcastNotification(new TestNotificationContent());

        expect(notificationRecordsCount())->toBe(1);
        expect(readsCount($alice->id))->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// createBroadcastNotification — external channels
// ---------------------------------------------------------------------------

describe('createBroadcastNotification — external channels', function () {
    it('calls the callback only for opted-in users on a default-OFF broadcast channel', function () {
        $calls = registerFakeChannel('discord', defaultEnabled: false);
        $alice  = alice($this);
        $bob    = bob($this);

        optInChannel($alice->id, TestNotificationContent::type(), 'discord');

        api()->createBroadcastNotification(new TestNotificationContent());

        expect($calls)->toHaveCount(1);
        expect($calls[0]['userIds'])->toBe([$alice->id]);
    });

    it('calls the callback for all users except opted-out on a default-ON broadcast channel', function () {
        $calls = registerFakeChannel('email', defaultEnabled: true);
        $alice  = alice($this);
        $bob    = bob($this);

        DB::table('notification_preferences')->insert([
            'user_id' => $alice->id,
            'type'    => TestNotificationContent::type(),
            'channel' => 'email',
            'enabled' => false,
        ]);

        api()->createBroadcastNotification(new TestNotificationContent());

        expect($calls)->toHaveCount(1);
        expect($calls[0]['userIds'])->toBe([$bob->id]);
    });

    it('skips the broadcast callback when no user opted in on default-OFF channel', function () {
        $calls = registerFakeChannel('discord', defaultEnabled: false);
        alice($this);
        bob($this);

        api()->createBroadcastNotification(new TestNotificationContent());

        expect($calls)->toBeEmpty();
    });
});
