<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Discord\Public\Events\DiscordConnected;
use App\Domains\Discord\Public\Events\DiscordDisconnected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $key = '__test_api_key__';
    putenv('DISCORD_BOT_API_KEY=' . $key);
    $_ENV['DISCORD_BOT_API_KEY'] = $key;
    $_SERVER['DISCORD_BOT_API_KEY'] = $key;
});

describe('Discord domain events', function () {
    it('emits Discord.Connected when a user links successfully', function () {
        $user = alice($this);
        $code = givenDiscordConnectCodeForUser($this, $user);
        $discordId = '123456789012345678';

        discordConnectWithCode($this, $code, [
            'discordId' => $discordId,
            'discordUsername' => 'DisplayName',
        ])->assertStatus(200);

        $event = latestEventOf(DiscordConnected::name(), DiscordConnected::class);
        expect($event)->not->toBeNull();
        $payload = $event->toPayload();
        expect($payload['userId'])->toBe($user->id);
        expect($payload['discordId'])->toBe($discordId);
    });

    it('emits Discord.Disconnected when a user unlinks successfully', function () {
        $user = alice($this);
        $code = givenDiscordConnectCodeForUser($this, $user);
        $discordId = '123456789012345678';

        discordConnectWithCode($this, $code, [
            'discordId' => $discordId,
            'discordUsername' => 'DisplayName',
        ])->assertStatus(200);

        discordDeleteUser($this, $discordId)->assertStatus(204);

        $event = latestEventOf(DiscordDisconnected::name(), DiscordDisconnected::class);
        expect($event)->not->toBeNull();
        $payload = $event->toPayload();
        expect($payload['userId'])->toBe($user->id);
        expect($payload['discordId'])->toBe($discordId);
    });
});
