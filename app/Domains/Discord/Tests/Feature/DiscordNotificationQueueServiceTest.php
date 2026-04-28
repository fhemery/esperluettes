<?php

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Discord\Private\Services\DiscordNotificationQueueService;
use App\Domains\Discord\Private\Support\DiscordFeatureToggles;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Discord\Tests\Fixtures\HtmlTestNotificationContent;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $key = '__test_api_key__';
    putenv('DISCORD_BOT_API_KEY=' . $key);
    $_ENV['DISCORD_BOT_API_KEY']    = $key;
    $_SERVER['DISCORD_BOT_API_KEY'] = $key;
});

// ---------------------------------------------------------------------------
// Channel registration
// ---------------------------------------------------------------------------

describe('Discord notification channel registration', function () {
    it('appears in getActiveChannels() when the feature toggle is ON', function () {
        createFeatureToggle($this, new FeatureToggle(
            name:   DiscordFeatureToggles::NOTIFICATIONS,
            domain: DiscordFeatureToggles::DOMAIN,
            access: FeatureToggleAccess::ON,
        ));

        $registry = app(NotificationChannelRegistry::class);
        $ids      = array_map(fn ($c) => $c->id, $registry->getActiveChannels());

        expect($ids)->toContain('discord');
    });

    it('is absent from getActiveChannels() when the toggle does not exist', function () {
        // No toggle in DB → isToggleEnabled returns false → channel inactive
        $registry = app(NotificationChannelRegistry::class);
        $ids      = array_map(fn ($c) => $c->id, $registry->getActiveChannels());

        expect($ids)->not->toContain('discord');
    });

    it('is absent from getActiveChannels() when the feature toggle is OFF', function () {
        createFeatureToggle($this, new FeatureToggle(
            name:   DiscordFeatureToggles::NOTIFICATIONS,
            domain: DiscordFeatureToggles::DOMAIN,
            access: FeatureToggleAccess::OFF,
        ));

        $registry = app(NotificationChannelRegistry::class);
        $ids      = array_map(fn ($c) => $c->id, $registry->getActiveChannels());

        expect($ids)->not->toContain('discord');
    });

    it('is registered as default-off (users must opt in)', function () {
        $registry = app(NotificationChannelRegistry::class);
        $channel  = $registry->get('discord');

        expect($channel)->not->toBeNull();
        expect($channel->defaultEnabled)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// DiscordNotificationQueueService
// ---------------------------------------------------------------------------

describe('DiscordNotificationQueueService::queue()', function () {
    /** @return NotificationDto */
    function makeDto(int $id = 1): NotificationDto
    {
        return new NotificationDto(
            id:          $id,
            type:        HtmlTestNotificationContent::type(),
            data:        ['message' => 'test'],
            htmlDisplay: 'test',
        );
    }

    it('creates one pending_notification and one recipient row for a linked user', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice, '111222333444555666');
        $notifId   = makeNotification([$alice->id]);

        app(DiscordNotificationQueueService::class)->queue(makeDto($notifId), [$alice->id]);

        expect(DB::table('discord_pending_notifications')->count())->toBe(1);
        expect(DB::table('discord_pending_recipients')->count())->toBe(1);

        $recipient = DB::table('discord_pending_recipients')->first();
        expect($recipient->discord_id)->toBe($discordId);
        expect($recipient->user_id)->toBe($alice->id);
        expect($recipient->sent_at)->toBeNull();
    });

    it('creates N recipient rows when multiple users have linked accounts', function () {
        $alice   = alice($this);
        $bob     = bob($this);
        linkDiscord($this, $alice, '111111111111111111', 'Alice');
        linkDiscord($this, $bob,   '222222222222222222', 'Bob');
        $notifId = makeNotification([$alice->id, $bob->id]);

        app(DiscordNotificationQueueService::class)->queue(makeDto($notifId), [$alice->id, $bob->id]);

        expect(DB::table('discord_pending_notifications')->count())->toBe(1);
        expect(DB::table('discord_pending_recipients')->count())->toBe(2);
    });

    it('skips users with no linked Discord account', function () {
        $alice   = alice($this);  // not linked
        $notifId = makeNotification([$alice->id]);

        app(DiscordNotificationQueueService::class)->queue(makeDto($notifId), [$alice->id]);

        expect(DB::table('discord_pending_notifications')->count())->toBe(0);
        expect(DB::table('discord_pending_recipients')->count())->toBe(0);
    });

    it('creates no pending_notification when all users are skipped', function () {
        $alice = alice($this);
        $bob   = bob($this);
        // Neither linked to Discord
        $notifId = makeNotification([$alice->id, $bob->id]);

        app(DiscordNotificationQueueService::class)->queue(makeDto($notifId), [$alice->id, $bob->id]);

        expect(DB::table('discord_pending_notifications')->count())->toBe(0);
    });

    it('creates one pending_notification even when some users are skipped', function () {
        $alice = alice($this);
        $bob   = bob($this);
        linkDiscord($this, $alice, '111111111111111111', 'Alice');
        // Bob not linked
        $notifId = makeNotification([$alice->id, $bob->id]);

        app(DiscordNotificationQueueService::class)->queue(makeDto($notifId), [$alice->id, $bob->id]);

        expect(DB::table('discord_pending_notifications')->count())->toBe(1);
        expect(DB::table('discord_pending_recipients')->count())->toBe(1);
    });
});
