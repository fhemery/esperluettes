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

describe('GET /api/discord/users/{discordId}', function () {
    it('returns 401 when Authorization header is missing', function () {
        $resp = $this->getJson('/api/discord/users/123456789012345678');

        $resp->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ]);
    });

    it('returns 401 when API key is invalid', function () {
        $resp = discordGetUser($this, '123456789012345678', [
            'Authorization' => 'Bearer invalid_key',
        ]);

        $resp->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ]);
    });

    it('returns 404 when discordId is not found', function () {
        $resp = discordGetUser($this, '999999999999999999');
        $resp->assertStatus(404)
            ->assertJsonPath('error', 'Not Found');
    });

    it('returns 200 with userId and roles when discordId exists', function () {
        $user = alice($this);
        $code = givenDiscordConnectCodeForUser($this, $user);
        // Link the discord user via the POST endpoint to ensure consistent flow
        discordConnectWithCode($this, $code, [
            'discordId' => '123456789012345678',
            'discordUsername' => 'DisplayName',
        ])->assertStatus(200);

        $resp = discordGetUser($this, '123456789012345678');
        $resp->assertStatus(200)
            ->assertJsonStructure([
                'userId',
                'roles',
            ])
            ->assertJsonPath('userId', $user->id);

        $roles = $resp->json('roles');
        $this->assertIsArray($roles);
        $this->assertContains(Roles::USER_CONFIRMED, $roles);
    });
});
