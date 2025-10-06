<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $key = '__test_api_key__';
    putenv('DISCORD_BOT_API_KEY=' . $key);
    $_ENV['DISCORD_BOT_API_KEY'] = $key;
    $_SERVER['DISCORD_BOT_API_KEY'] = $key;
});

it('removes discord associations when a user account is deleted', function () {
    // Arrange: a confirmed user, connected to Discord
    $user = alice($this, roles: [Roles::USER_CONFIRMED]);
    $code = givenDiscordConnectCodeForUser($this, $user);
    $discordId = '123456789012345678';

    discordConnectWithCode($this, $code, [
        'discordId' => $discordId,
        'discordUsername' => 'DisplayName',
    ])->assertStatus(200);

    // Sanity check: row exists
    discordGetUser($this, $discordId)->assertStatus(200);

    // Act: delete the user via real controller flow
    deleteUser($this, $user);

    // Assert: association removed
    discordGetUser($this, $discordId)->assertStatus(404);
});
