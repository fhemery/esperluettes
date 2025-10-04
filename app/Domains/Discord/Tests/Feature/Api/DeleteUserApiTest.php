<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure the middleware sees a configured API key during tests
    $key = '__test_api_key__';
    putenv('DISCORD_BOT_API_KEY=' . $key);
    $_ENV['DISCORD_BOT_API_KEY'] = $key;
    $_SERVER['DISCORD_BOT_API_KEY'] = $key;
});

describe('DELETE /api/discord/users/{discordId}', function () {
    it('returns 401 when Authorization header is missing', function () {
        $resp = $this->deleteJson('/api/discord/users/123456789012345678');

        $resp->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ]);
    });

    it('returns 401 when API key is invalid', function () {
        $resp = discordDeleteUser($this, '123456789012345678', [
            'Authorization' => 'Bearer invalid_key',
        ]);

        $resp->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ]);
    });

    it('returns 404 when discordId is not found', function () {
        $resp = discordDeleteUser($this, '999999999999999999');
        $resp->assertStatus(404)
            ->assertJsonPath('error', 'Not Found');
    });

    it('returns 204 when discordId exists, then GET returns 404', function () {
        // Arrange: link a user via the POST connect flow first
        $user = alice($this);
        $code = givenDiscordConnectCodeForUser($this, $user);
        discordConnectWithCode($this, $code, [
            'discordId' => '123456789012345678',
            'discordUsername' => 'DisplayName',
        ])->assertStatus(200);

        // Act: delete mapping
        $resp = discordDeleteUser($this, '123456789012345678');
        $resp->assertStatus(204);

        // Assert: cannot retrieve afterward
        discordGetUser($this, '123456789012345678')->assertStatus(404);
    });
});
