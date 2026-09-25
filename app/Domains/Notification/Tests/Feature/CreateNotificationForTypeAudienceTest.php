<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Notification\Tests\Fixtures\StaffOnlyTestNotificationContent;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
    $factory->register(
        type: StaffOnlyTestNotificationContent::type(),
        class: StaffOnlyTestNotificationContent::class,
        groupId: 'test',
        nameKey: 'test.staff',
        visibleToRoles: [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
    );
    app()->instance(NotificationFactory::class, $factory);
    app()->instance(NotificationChannelRegistry::class, new NotificationChannelRegistry());
});

function audienceApi(): NotificationPublicApi
{
    return app(NotificationPublicApi::class);
}

function registerAudienceFakeChannel(string $id = 'fake'): \ArrayObject
{
    $calls = new \ArrayObject([]);
    app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
        id: $id,
        nameTranslationKey: 'test::channel',
        defaultEnabled: false,
        sortOrder: 1,
        deliveryCallback: function (NotificationDto $dto, array $userIds) use ($calls) {
            $calls->append(['dto' => $dto, 'userIds' => $userIds]);
        },
    ));

    return $calls;
}

describe('createNotificationForTypeAudience', function () {
    it('throws when the type has no visibleToRoles', function () {
        expect(fn () => audienceApi()->createNotificationForTypeAudience(new TestNotificationContent()))
            ->toThrow(ValidationException::class);
    });

    it('is a silent no-op when the audience is empty after excluding the actor', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);

        audienceApi()->createNotificationForTypeAudience(
            new StaffOnlyTestNotificationContent(),
            excludeUserId: $moderator->id,
        );

        expect(getLatestNotificationByKey(StaffOnlyTestNotificationContent::type()))->toBeNull();
    });

    it('delivers to role holders respecting website opt-out and Discord opt-in', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $otherStaff = bob($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
        $regular = carol($this, roles: [Roles::USER_CONFIRMED]);

        DB::table('notification_preferences')->insert([
            'user_id' => $otherStaff->id,
            'type'    => StaffOnlyTestNotificationContent::type(),
            'channel' => 'website',
            'enabled' => false,
        ]);

        $discordCalls = registerAudienceFakeChannel('discord');
        DB::table('notification_preferences')->insert([
            'user_id' => $moderator->id,
            'type'    => StaffOnlyTestNotificationContent::type(),
            'channel' => 'discord',
            'enabled' => true,
        ]);

        audienceApi()->createNotificationForTypeAudience(new StaffOnlyTestNotificationContent());

        $notification = getLatestNotificationByKey(StaffOnlyTestNotificationContent::type());
        expect($notification)->not->toBeNull();

        expect(getNotificationTargetUserIds((int) $notification->id))
            ->toContain($moderator->id)
            ->not->toContain($otherStaff->id)
            ->not->toContain($regular->id);

        expect($discordCalls->count())->toBe(1);
        expect($discordCalls[0]['userIds'])->toContain($moderator->id);
        expect($discordCalls[0]['userIds'])->not->toContain($otherStaff->id);
    });
});
