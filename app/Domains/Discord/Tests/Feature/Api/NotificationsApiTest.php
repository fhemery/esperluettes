<?php

use App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository;
use App\Domains\Discord\Tests\Fixtures\HtmlTestNotificationContent;
use App\Domains\Notification\Public\Services\NotificationFactory;
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

// ---------------------------------------------------------------------------
// GET /api/discord/notifications/pending
// ---------------------------------------------------------------------------

describe('GET /api/discord/notifications/pending', function () {

    describe('authentication', function () {
        it('returns 401 when Authorization header is missing', function () {
            discordGetPendingNotifications($this, [], ['Authorization' => null])
                ->assertStatus(401)
                ->assertJson(['error' => 'Unauthorized']);
        });

        it('returns 401 when API key is invalid', function () {
            discordGetPendingNotifications($this, [], ['Authorization' => 'Bearer wrong'])
                ->assertStatus(401);
        });
    });

    it('returns empty data when no pending notifications exist', function () {
        $resp = discordGetPendingNotifications($this);

        $resp->assertStatus(200)
            ->assertJson([
                'data'       => [],
                'pagination' => [
                    'currentPage' => 1,
                    'total'       => 0,
                    'hasMore'     => false,
                ],
            ]);
    });

    it('returns a pending notification with its recipients', function () {
        $alice   = alice($this);
        $discordId = linkDiscord($this, $alice, '111222333444555666');

        $notifId = makeNotification([$alice->id], new TestNotificationContent('hello world'));
        $pending = queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $discordId],
        ]);

        $resp = discordGetPendingNotifications($this);

        $resp->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('data.0.type', TestNotificationContent::type())
            ->assertJsonPath('data.0.recipients', [$discordId]);
    });

    it('includes the correct data fields for the notification', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);
        $notifId   = makeNotification([$alice->id], new TestNotificationContent('hello'));
        queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);

        $resp = discordGetPendingNotifications($this);

        $resp->assertStatus(200);
        $item = $resp->json('data.0');
        expect($item['data']['message'])->toBe('hello');
        expect($item['defaultText'])->toBeString();
        expect($item['createdAt'])->toBeString();
    });

    it('converts HTML links in htmlDisplay to Discord markdown in defaultText', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);

        // Register the fixture type so getNotificationsByIds() can reconstruct it
        $factory = app(NotificationFactory::class);
        $factory->registerGroup('discord_test', 99, 'discord_test::group');
        $factory->register(
            type:    HtmlTestNotificationContent::type(),
            class:   HtmlTestNotificationContent::class,
            groupId: 'discord_test',
            nameKey: 'discord_test::type',
        );

        $notifId = makeNotification([$alice->id], new HtmlTestNotificationContent());
        queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);

        $resp = discordGetPendingNotifications($this);

        $resp->assertStatus(200);
        expect($resp->json('data.0.defaultText'))->toBe('[click here](https://example.com)');
        expect($resp->json('data.0.data.message'))->toBe('click here');
    });

    it('excludes notifications where all recipients are already sent', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);
        $notifId   = makeNotification([$alice->id]);
        $pending   = queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $discordId],
        ]);

        // Mark as sent
        app(DiscordPendingNotificationRepository::class)->markAllRecipientsDelivered($pending->id);

        discordGetPendingNotifications($this)
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    it('skips pending entries whose notification was deleted', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);
        $notifId   = makeNotification([$alice->id]);
        queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);

        // Delete the source notification
        DB::table('notifications')->where('id', $notifId)->delete();

        $resp = discordGetPendingNotifications($this);
        $resp->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    it('returns multiple recipients for the same notification', function () {
        $alice     = alice($this);
        $bob       = bob($this);
        $aliceDiscordId = linkDiscord($this, $alice, '111111111111111111', 'Alice');
        $bobDiscordId   = linkDiscord($this, $bob, '222222222222222222', 'Bob');

        $notifId = makeNotification([$alice->id, $bob->id]);
        queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $aliceDiscordId],
            ['user_id' => $bob->id,   'discord_id' => $bobDiscordId],
        ]);

        $resp = discordGetPendingNotifications($this);

        $resp->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $recipients = $resp->json('data.0.recipients');
        expect($recipients)->toContain($aliceDiscordId);
        expect($recipients)->toContain($bobDiscordId);
    });

    it('respects pagination parameters', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);

        // Create 3 separate notifications
        for ($i = 0; $i < 3; $i++) {
            $notifId = makeNotification([$alice->id], new TestNotificationContent("msg $i"));
            queueDiscordNotification($notifId, [['user_id' => $alice->id, 'discord_id' => $discordId]]);
        }

        $resp = discordGetPendingNotifications($this, ['perPage' => 2, 'page' => 1]);

        $resp->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.hasMore', true)
            ->assertJsonPath('pagination.lastPage', 2);
    });
});

// ---------------------------------------------------------------------------
// POST /api/discord/notifications/mark-sent
// ---------------------------------------------------------------------------

describe('POST /api/discord/notifications/mark-sent', function () {

    describe('authentication', function () {
        it('returns 401 when Authorization header is missing', function () {
            $this->postJson('/api/discord/notifications/mark-sent', ['notifications' => []])
                ->assertStatus(401);
        });
    });

    it('accepts an empty notifications array as a no-op', function () {
        discordMarkSent($this, [])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'markedCount' => 0]);
    });

    it('returns 422 when notifications field is absent', function () {
        $this->postJson(
            '/api/discord/notifications/mark-sent',
            [],
            ['Authorization' => 'Bearer __test_api_key__', 'Accept' => 'application/json']
        )->assertStatus(422);
    });

    it('marks all recipients as delivered when no failedRecipients given', function () {
        $alice     = alice($this);
        $discordId = linkDiscord($this, $alice);
        $notifId   = makeNotification([$alice->id]);
        $pending   = queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $discordId],
        ]);

        $resp = discordMarkSent($this, [['id' => $pending->id]]);

        $resp->assertStatus(200)
            ->assertJson(['success' => true, 'markedCount' => 1]);

        // Should no longer appear in pending
        discordGetPendingNotifications($this)
            ->assertJson(['data' => []]);
    });

    it('marks only non-failed recipients as delivered when failedRecipients given', function () {
        $alice     = alice($this);
        $bob       = bob($this);
        $aliceId   = linkDiscord($this, $alice, '111111111111111111', 'Alice');
        $bobId     = linkDiscord($this, $bob,   '222222222222222222', 'Bob');
        $notifId   = makeNotification([$alice->id, $bob->id]);
        $pending   = queueDiscordNotification($notifId, [
            ['user_id' => $alice->id, 'discord_id' => $aliceId],
            ['user_id' => $bob->id,   'discord_id' => $bobId],
        ]);

        $resp = discordMarkSent($this, [[
            'id'               => $pending->id,
            'failedRecipients' => [$bobId],
        ]]);

        $resp->assertStatus(200)
            ->assertJson(['success' => true, 'markedCount' => 1]);

        // Bob's row is still pending → notification still shows up
        $pendingResp = discordGetPendingNotifications($this);
        $pendingResp->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $recipients = $pendingResp->json('data.0.recipients');
        expect($recipients)->toContain($bobId);
        expect($recipients)->not->toContain($aliceId);
    });

    it('silently ignores unknown notification IDs', function () {
        $resp = discordMarkSent($this, [['id' => 999999]]);
        $resp->assertStatus(200)
            ->assertJson(['success' => true, 'markedCount' => 0]);
    });

    it('returns correct total markedCount across multiple notifications', function () {
        $alice   = alice($this);
        $aliceId = linkDiscord($this, $alice, '111111111111111111', 'Alice');

        $notifId1 = makeNotification([$alice->id], new TestNotificationContent('first'));
        $pending1 = queueDiscordNotification($notifId1, [['user_id' => $alice->id, 'discord_id' => $aliceId]]);

        $notifId2 = makeNotification([$alice->id], new TestNotificationContent('second'));
        $pending2 = queueDiscordNotification($notifId2, [['user_id' => $alice->id, 'discord_id' => $aliceId]]);

        $resp = discordMarkSent($this, [
            ['id' => $pending1->id],
            ['id' => $pending2->id],
        ]);

        $resp->assertStatus(200)
            ->assertJson(['success' => true, 'markedCount' => 2]);
    });
});
