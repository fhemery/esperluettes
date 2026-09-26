<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Public\Notifications\ReportSubmittedNotification;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function submitReportAs(TestCase $t, $user, string $description = 'Secret reason details'): void
{
    $reason = createReason('profile', 'Spam');

    $t->actingAs($user)
        ->postJson('/moderation/report', [
            'topic_key' => 'profile',
            'entity_id' => 123,
            'reason_id' => $reason->id,
            'description' => $description,
        ])
        ->assertOk();
}

describe('report submitted staff notification', function () {
    it('notifies other active staff when a report is submitted', function () {
        $reporter = alice($this);
        $moderator = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $admin = carol($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
        $techAdmin = daniel($this, roles: [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]);

        submitReportAs($this, $reporter);

        $notification = getLatestNotificationByKey(ReportSubmittedNotification::type());
        expect($notification)->not->toBeNull();
        expect($notification->source_user_id)->toBe($reporter->id);
        expect(getNotificationTargetUserIds((int) $notification->id))
            ->toEqualCanonicalizing([$moderator->id, $admin->id, $techAdmin->id]);

        $content = ReportSubmittedNotification::fromData($notification->content_data);
        expect($content->toData())->toBe(['user_name' => 'Alice']);

        app()->setLocale('fr');
        $html = $content->display();
        expect($html)
            ->toContain('Alice')
            ->toContain(route('moderation.admin.moderation-reports.index'))
            ->not->toContain('Spam')
            ->not->toContain('Secret reason details');
    });

    it('excludes the reporter from staff notification recipients', function () {
        $reporter = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $otherStaff = bob($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);

        submitReportAs($this, $reporter);

        $notification = getLatestNotificationByKey(ReportSubmittedNotification::type());
        expect($notification)->not->toBeNull();
        expect(getNotificationTargetUserIds((int) $notification->id))
            ->toBe([$otherStaff->id]);
        expect(notificationReadRow($reporter->id, (int) $notification->id))->toBeNull();
    });

    it('does not notify non-staff users', function () {
        $reporter = alice($this);
        $moderator = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $confirmed = carol($this);
        $unconfirmed = daniel($this, roles: [Roles::USER]);

        submitReportAs($this, $reporter);

        $notification = getLatestNotificationByKey(ReportSubmittedNotification::type());
        $targets = getNotificationTargetUserIds((int) $notification->id);
        expect($targets)->toBe([$moderator->id]);
        expect($targets)->not->toContain($confirmed->id)->not->toContain($unconfirmed->id);
    });

    it('does not notify deactivated staff accounts', function () {
        $reporter = alice($this);
        $activeStaff = bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $inactiveStaff = carol($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
        deactivateUser($inactiveStaff);

        submitReportAs($this, $reporter);

        $notification = getLatestNotificationByKey(ReportSubmittedNotification::type());
        expect(getNotificationTargetUserIds((int) $notification->id))->toBe([$activeStaff->id]);
    });

    it('creates no notification when the reporter is the only staff member', function () {
        $reporter = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        bob($this);

        submitReportAs($this, $reporter);

        $this->assertDatabaseHas('moderation_reports', ['reported_by_user_id' => $reporter->id]);
        expect(getLatestNotificationByKey(ReportSubmittedNotification::type()))->toBeNull();
    });

    it('honours website opt-out and Discord opt-in for staff recipients', function () {
        $reporter = alice($this);
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
            ['user_id' => $optedOut->id, 'type' => ReportSubmittedNotification::type(), 'channel' => 'website', 'enabled' => false],
            ['user_id' => $discordOptIn->id, 'type' => ReportSubmittedNotification::type(), 'channel' => 'discord', 'enabled' => true],
        ]);

        submitReportAs($this, $reporter);

        $notification = getLatestNotificationByKey(ReportSubmittedNotification::type());
        expect(getNotificationTargetUserIds((int) $notification->id))->toBe([$discordOptIn->id]);

        expect($discordCalls->count())->toBe(1);
        expect($discordCalls[0]['userIds'])->toBe([$discordOptIn->id]);
    });

    it('still creates the report when notification dispatch throws', function () {
        $reporter = alice($this);
        bob($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);

        $mock = Mockery::mock(NotificationPublicApi::class);
        $mock->shouldReceive('createNotificationForTypeAudience')->andThrow(new \RuntimeException('boom'));
        app()->instance(NotificationPublicApi::class, $mock);

        submitReportAs($this, $reporter);

        $this->assertDatabaseHas('moderation_reports', [
            'reported_by_user_id' => $reporter->id,
            'status' => 'pending',
        ]);
    });
});
