<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository;
use App\Domains\Discord\Private\Services\DiscordNotificationQueueService;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
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

describe('Discord notification cleanup on disconnect', function () {
    it('removes pending recipients for a user when they disconnect via the bot API', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice, '111111111111111111', 'Alice');
        $notifId   = makeNotification([$alice->id]);

        queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);
        expect(DB::table('discord_pending_recipients')->where('user_id', $alice->id)->count())->toBe(1);

        // Disconnect via API — this fires DiscordDisconnected → CleanDiscordNotificationsOnDisconnect
        discordDeleteUser($this, $discordId)->assertStatus(204);

        expect(DB::table('discord_pending_recipients')->where('user_id', $alice->id)->count())->toBe(0);
    });

    it('only removes the disconnecting user\'s recipients, leaving siblings intact', function () {
        $alice   = alice($this);
        $bob     = bob($this);
        $aliceId = linkDiscord($this, $alice, '111111111111111111', 'Alice');
        $bobId   = linkDiscord($this, $bob,   '222222222222222222', 'Bob');
        $notifId = makeNotification([$alice->id, $bob->id]);

        queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $aliceId],
            ['user_id' => $bob->id,   'discord_id' => $bobId],
        ]);

        // Disconnect Alice only
        discordDeleteUser($this, $aliceId)->assertStatus(204);

        expect(DB::table('discord_pending_recipients')->where('user_id', $alice->id)->count())->toBe(0);
        expect(DB::table('discord_pending_recipients')->where('user_id', $bob->id)->count())->toBe(1);
    });

    it('removes pending recipients when a user account is deleted', function () {
        $alice     = alice($this, roles: [Roles::USER_CONFIRMED]);
        $discordId = linkDiscord($this, $alice, '111111111111111111', 'Alice');
        $notifId   = makeNotification([$alice->id]);

        queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);
        expect(DB::table('discord_pending_recipients')->where('user_id', $alice->id)->count())->toBe(1);

        deleteUser($this, $alice);

        expect(DB::table('discord_pending_recipients')->where('user_id', $alice->id)->count())->toBe(0);
    });
});

describe('Discord pending notification cascade', function () {
    it('cascades deletion from discord_pending_notifications to discord_pending_recipients', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);
        $notifId   = makeNotification([$alice->id]);
        $pending   = queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $discordId],
        ]);

        expect(DB::table('discord_pending_recipients')->count())->toBe(1);

        DB::table('discord_pending_notifications')->where('id', $pending->id)->delete();

        expect(DB::table('discord_pending_recipients')->count())->toBe(0);
    });

    it('cascades deletion from notifications to discord_pending_notifications', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);
        $notifId   = makeNotification([$alice->id]);
        queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);

        expect(DB::table('discord_pending_notifications')->count())->toBe(1);
        expect(DB::table('discord_pending_recipients')->count())->toBe(1);

        DB::table('notifications')->where('id', $notifId)->delete();

        expect(DB::table('discord_pending_notifications')->count())->toBe(0);
        expect(DB::table('discord_pending_recipients')->count())->toBe(0);
    });
});

describe('DiscordPendingNotificationRepository::deleteRecipientsForUser', function () {
    it('deletes only rows for the specified user', function () {
        $alice   = alice($this);
        $bob     = bob($this);
        $aliceId = linkDiscord($this, $alice, '111111111111111111', 'Alice');
        $bobId   = linkDiscord($this, $bob,   '222222222222222222', 'Bob');
        $notifId = makeNotification([$alice->id, $bob->id]);

        queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $aliceId],
            ['user_id' => $bob->id,   'discord_id' => $bobId],
        ]);

        app(DiscordPendingNotificationRepository::class)->deleteRecipientsForUser($alice->id);

        expect(DB::table('discord_pending_recipients')->where('user_id', $alice->id)->count())->toBe(0);
        expect(DB::table('discord_pending_recipients')->where('user_id', $bob->id)->count())->toBe(1);
    });
});
