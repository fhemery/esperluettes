<?php

use App\Domains\Auth\Private\Services\RoleService;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Notifications\PromotionRequestedNotification;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
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

    $this->discordCalls = new \ArrayObject([]);
    $calls = $this->discordCalls;
    $registry = new NotificationChannelRegistry();
    $registry->register(new NotificationChannelDefinition(
        id: 'discord',
        nameTranslationKey: 'test::channel',
        defaultEnabled: false,
        sortOrder: 1,
        deliveryCallback: function (NotificationDto $dto, array $userIds) use ($calls) {
            $calls->append(['dto' => $dto, 'userIds' => $userIds]);
        },
    ));
    app()->instance(NotificationChannelRegistry::class, $registry);

    $this->requestPromotion = function ($requester): void {
        $result = app(AuthPublicApi::class)->requestPromotion($requester->id, commentCount: 5);
        expect($result->success)->toBeTrue();
    };
});

afterEach(function () {
    clearSettingsRegistry();
});

function lifecycleSeedDiscordOptIn(int $userId): void
{
    DB::table('notification_preferences')->insert([
        'user_id' => $userId,
        'type' => PromotionRequestedNotification::type(),
        'channel' => 'discord',
        'enabled' => true,
    ]);
}

function lifecycleStoredPrefs(int $userId): array
{
    return DB::table('notification_preferences')
        ->where('user_id', $userId)
        ->where('type', PromotionRequestedNotification::type())
        ->orderBy('channel')
        ->get(['channel', 'enabled'])
        ->map(fn ($row) => [$row->channel, (bool) $row->enabled])
        ->all();
}

/** Returns the rendered checkbox <input> for a preference toggle, or null when the row is absent. */
function lifecyclePrefCheckbox(string $html, string $channel): ?string
{
    $name = preg_quote('prefs['.PromotionRequestedNotification::type().']['.$channel.']', '/');
    if (! preg_match('/<input[^>]*type="checkbox"[^>]*name="'.$name.'"[^>]*>/s', $html, $m)) {
        return null;
    }

    return $m[0];
}

function lifecycleIsChecked(string $input): bool
{
    return (bool) preg_match('/\schecked[\s>]/', $input);
}

describe('staff notification role lifecycle', function () {
    it('hides staff notification preference rows after the user loses their last staff role', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);

        $before = $this->actingAs($moderator)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->getContent();
        expect(lifecyclePrefCheckbox($before, 'website'))->not->toBeNull();

        app(RoleService::class)->revoke($moderator, Roles::MODERATOR);

        $this->actingAs($moderator)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertDontSee('prefs['.PromotionRequestedNotification::type().']', false)
            // requester-facing rows of the same group stay
            ->assertSee('prefs[auth.promotion.accepted][website]', false)
            ->assertSee('prefs[auth.promotion.rejected][website]', false);
    });

    it('stops delivering staff notifications after demotion', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        $otherStaff = bob($this, roles: [Roles::ADMIN, Roles::USER_CONFIRMED]);
        lifecycleSeedDiscordOptIn($moderator->id);

        ($this->requestPromotion)(carol($this, roles: [Roles::USER]));

        $first = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect(getNotificationTargetUserIds((int) $first->id))
            ->toEqualCanonicalizing([$moderator->id, $otherStaff->id]);
        expect($this->discordCalls->count())->toBe(1);
        expect($this->discordCalls[0]['userIds'])->toBe([$moderator->id]);

        app(RoleService::class)->revoke($moderator, Roles::MODERATOR);

        ($this->requestPromotion)(daniel($this, roles: [Roles::USER]));

        expect(countNotificationsByKey(PromotionRequestedNotification::type()))->toBe(2);
        $second = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect($second->id)->not->toBe($first->id);
        // stale prefs (Discord opt-in) are still stored, yet the demoted user is not a recipient
        expect(getNotificationTargetUserIds((int) $second->id))->toBe([$otherStaff->id]);
        expect(notificationReadRow($moderator->id, (int) $second->id))->toBeNull();
        expect($this->discordCalls->count())->toBe(1);
    });

    it('keeps stored staff-type preferences across demotion', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        lifecycleSeedDiscordOptIn($moderator->id);

        $this->actingAs($moderator)
            ->putJson(route('notification.preferences.update', ['type' => PromotionRequestedNotification::type()]), [
                'channel' => 'website',
                'enabled' => false,
            ])
            ->assertOk();

        $stored = lifecycleStoredPrefs($moderator->id);
        expect($stored)->toBe([['discord', true], ['website', false]]);

        app(RoleService::class)->revoke($moderator, Roles::MODERATOR);

        expect(lifecycleStoredPrefs($moderator->id))->toBe($stored);
    });

    it('restores preference rows with stored choices after re-promotion', function () {
        $moderator = alice($this, roles: [Roles::MODERATOR, Roles::USER_CONFIRMED]);
        lifecycleSeedDiscordOptIn($moderator->id);
        DB::table('notification_preferences')->insert([
            'user_id' => $moderator->id,
            'type' => PromotionRequestedNotification::type(),
            'channel' => 'website',
            'enabled' => false,
        ]);

        $roles = app(RoleService::class);
        $roles->revoke($moderator, Roles::MODERATOR);

        $this->actingAs($moderator)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->assertDontSee('prefs['.PromotionRequestedNotification::type().']', false);

        $roles->grant($moderator, Roles::MODERATOR);

        $html = $this->actingAs($moderator)
            ->get(route('settings.tab', ['tab' => 'notification']))
            ->assertOk()
            ->getContent();

        $website = lifecyclePrefCheckbox($html, 'website');
        $discord = lifecyclePrefCheckbox($html, 'discord');
        expect($website)->not->toBeNull();
        expect($discord)->not->toBeNull();
        expect(lifecycleIsChecked($website))->toBeFalse();
        expect(lifecycleIsChecked($discord))->toBeTrue();

        // and the restored choices drive delivery again
        ($this->requestPromotion)(bob($this, roles: [Roles::USER]));

        $notification = getLatestNotificationByKey(PromotionRequestedNotification::type());
        expect(getNotificationTargetUserIds((int) $notification->id))->not->toContain($moderator->id);
        expect($this->discordCalls->count())->toBe(1);
        expect($this->discordCalls[0]['userIds'])->toBe([$moderator->id]);
    });
});
