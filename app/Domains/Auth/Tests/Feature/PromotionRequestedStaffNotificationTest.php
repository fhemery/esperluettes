<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Notifications\PromotionRequestedNotification;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    setParameterValue(AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD, AuthConfigKeys::DOMAIN, 0);
    setParameterValue(AuthConfigKeys::NON_CONFIRMED_TIMESPAN, AuthConfigKeys::DOMAIN, 0);
});

function submitPromotionRequestFor($user): void
{
    $result = app(AuthPublicApi::class)->requestPromotion($user->id, commentCount: 5);
    expect($result->success)->toBeTrue();
}

describe('promotion requested staff notification', function () {
    it('notifies other active staff when a promotion request is submitted', function () {
        $requester = alice($this, roles: [Roles::USER]);
        $moderator = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $admin = carol($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
        $techAdmin = daniel($this, roles: [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]);

        submitPromotionRequestFor($requester);

        $notification = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect($notification)->not->toBeNull();
        expect($notification->source_user_id)->toBe($requester->id);
        expect(getNotificationTargetUserIds((int) $notification->id))
            ->toEqualCanonicalizing([$moderator->id, $admin->id, $techAdmin->id]);

        $content = PromotionRequestedNotification::fromData($notification->content_data);
        expect($content->toData())->toBe(['user_name' => 'Alice']);

        app()->setLocale('fr');
        expect($content->display())
            ->toContain('Alice')
            ->toContain(route('auth.admin.promotion-requests.index'));
    });

    it('excludes the requester from staff notification recipients', function () {
        $requester = alice($this, roles: [Roles::MODERATOR, Roles::USER]);
        $otherStaff = bob($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);

        submitPromotionRequestFor($requester);

        $notification = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect($notification)->not->toBeNull();
        expect(getNotificationTargetUserIds((int) $notification->id))->toBe([$otherStaff->id]);
        expect(notificationReadRow($requester->id, (int) $notification->id))->toBeNull();
    });

    it('does not notify non-staff users', function () {
        $requester = alice($this, roles: [Roles::USER]);
        $moderator = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $confirmed = carol($this);
        $unconfirmed = daniel($this, roles: [Roles::USER]);

        submitPromotionRequestFor($requester);

        $notification = getLatestNotificationByKey(PromotionRequestedNotification::type());
        $targets = getNotificationTargetUserIds((int) $notification->id);
        expect($targets)->toBe([$moderator->id]);
        expect($targets)->not->toContain($confirmed->id)->not->toContain($unconfirmed->id);
    });

    it('does not notify deactivated staff accounts', function () {
        $requester = alice($this, roles: [Roles::USER]);
        $activeStaff = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $inactiveStaff = carol($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
        deactivateUser($inactiveStaff);

        submitPromotionRequestFor($requester);

        $notification = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect(getNotificationTargetUserIds((int) $notification->id))->toBe([$activeStaff->id]);
    });

    it('honours website opt-out and Discord opt-in for staff recipients', function () {
        $requester = alice($this, roles: [Roles::USER]);
        $optedOut = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $discordOptIn = carol($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);

        $discordCalls = new \ArrayObject([]);
        $registry = new NotificationChannelRegistry();
        $registry->register(new NotificationChannelDefinition(
            id: 'discord',
            nameTranslationKey: 'test::channel',
            defaultEnabled: false,
            sortOrder: 1,
            deliveryCallback: function (NotificationDto $dto, array $userIds) use ($discordCalls) {
                $discordCalls->append(['dto' => $dto, 'userIds' => $userIds]);
            },
        ));
        app()->instance(NotificationChannelRegistry::class, $registry);

        DB::table('notification_preferences')->insert([
            ['user_id' => $optedOut->id, 'type' => PromotionRequestedNotification::type(), 'channel' => 'website', 'enabled' => false],
            ['user_id' => $discordOptIn->id, 'type' => PromotionRequestedNotification::type(), 'channel' => 'discord', 'enabled' => true],
        ]);

        submitPromotionRequestFor($requester);

        $notification = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect(getNotificationTargetUserIds((int) $notification->id))->toBe([$discordOptIn->id]);

        expect($discordCalls->count())->toBe(1);
        expect($discordCalls[0]['userIds'])->toBe([$discordOptIn->id]);
    });

    it('still creates the promotion request when notification dispatch throws', function () {
        $requester = alice($this, roles: [Roles::USER]);
        bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);

        $mock = Mockery::mock(NotificationPublicApi::class);
        $mock->shouldReceive('createNotificationForTypeAudience')->andThrow(new \RuntimeException('boom'));
        app()->instance(NotificationPublicApi::class, $mock);

        submitPromotionRequestFor($requester);

        $this->assertDatabaseHas('user_promotion_request', [
            'user_id' => $requester->id,
            'status' => 'pending',
        ]);
    });
});
