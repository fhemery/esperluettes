<?php

use App\Domains\Auth\Public\Api\Roles;
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

describe('connect', function () {

    describe('error cases', function () {
        it('returns 401 when Authorization header is missing on connect', function () {
            $response = $this->postJson('/api/discord/users', [
                'code' => 'deadbeef',
                'discordId' => '123456789012345678',
                'discordUsername' => 'DisplayName',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid API key',
                ]);
        });

        it('returns 401 when API key is invalid on connect', function () {
            $response = discordConnect($this, discordConnectPayload('deadbeef'), [
                'Authorization' => 'Bearer invalid_key',
            ]);

            $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid API key',
                ]);
        });

        it('returns 409 when current user is already linked to a different discordId', function () {
            $user = alice($this);

            // User already linked to some discord id
            $discordUserId = '111111111111111111';
            $code = givenDiscordConnectCodeForUser($this, $user);
            discordConnectWithCode($this, $code, [
                'discordId' => $discordUserId,
                'discordUsername' => 'OldName',
            ]);

            // Valid code for same user
            $code = givenDiscordConnectCodeForUser($this, $user);
            $resp = discordConnectWithCode($this, $code, [
                'discordId' => '222222222222222222',
                'discordUsername' => 'NewName',
            ]);

            $resp->assertStatus(409)
                ->assertJsonPath('error', 'Conflict');
        });
    });

    describe('conflicts (409)', function () {
        it('returns 409 when discordId is already linked to another user', function () {
            $userA = alice($this);
            $userB = bob($this);

            $discordUserId = '123456789012345678';

            // Arrange: discordId already linked to userA
            $aliceCode = givenDiscordConnectCodeForUser($this, $userA);
            discordConnectWithCode($this, $aliceCode, [
                'discordId' => $discordUserId,
                'discordUsername' => 'Alice',
            ]);


            // Arrange: valid code for userB
            // Try to connect with discordId already linked to userA
            $bobCode = givenDiscordConnectCodeForUser($this, $userB);
            $resp = discordConnectWithCode($this, $bobCode, [
                'discordId' => $discordUserId,
                'discordUsername' => 'Bob',
            ]);

            $resp->assertStatus(409)
                ->assertJsonPath('error', 'Conflict');
        });
    });

    describe('not found / expired / used / invalidated (404)', function () {
        it('returns 404 when code does not exist', function () {
            $payload = discordConnectPayload('ffffffff');
            $resp = discordConnect($this, $payload);
            $resp->assertStatus(404)
                ->assertJsonPath('error', 'Invalid code');
        });

        it('returns 404 when code is expired', function () {
            $user = alice($this);
            $code = givenDiscordConnectCodeForUser($this, $user);

            // Expire it directly in DB
            \App\Domains\Discord\Private\Models\DiscordConnectionCode::query()
                ->where('code', $code)
                ->update(['expires_at' => now()->subMinute(6)]);

            $resp = discordConnectWithCode($this, $code);
            $resp->assertStatus(404)
                ->assertJsonPath('error', 'Invalid code');
        });

        it('returns 404 when code has already been used', function () {
            $user = alice($this);
            $code = givenDiscordConnectCodeForUser($this, $user);

            // First successful consumption
            discordConnectWithCode($this, $code)->assertStatus(200);

            // Second try should 404
            $resp2 = discordConnectWithCode($this, $code);
            $resp2->assertStatus(404)
                ->assertJsonPath('error', 'Invalid code');
        });

        it('returns 404 when a new code has been requested after this one', function () {
            $user = alice($this);
            $oldCode = givenDiscordConnectCodeForUser($this, $user);
            // Request a new one which invalidates previous
            $newCode = givenDiscordConnectCodeForUser($this, $user);

            // Old code should now 404
            $payloadOld = discordConnectPayload($oldCode);
            $respOld = discordConnect($this, $payloadOld);
            $respOld->assertStatus(404)
                ->assertJsonPath('error', 'Invalid code');

            // New code still works
            $payloadNew = discordConnectPayload($newCode);
            discordConnect($this, $payloadNew)->assertStatus(200);
        });
    });

    describe('validation (400)', function () {
        it('returns 400 when code is missing', function () {
            $payload = discordConnectPayload();
            unset($payload['code']);
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['code'],
                ]);
        });

        it('returns 400 when discordId is missing', function () {
            $payload = discordConnectPayload();
            unset($payload['discordId']);
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordId'],
                ]);
        });

        it('returns 400 when discordUsername is missing', function () {
            $payload = discordConnectPayload();
            unset($payload['discordUsername']);
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordUsername'],
                ]);
        });

        it('returns 400 when code is only whitespace', function () {
            $payload = discordConnectPayload();
            $payload['code'] = '   ';
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['code'],
                ]);
        });

        it('returns 400 when discordUsername is empty', function () {
            $payload = discordConnectPayload();
            $payload['discordUsername'] = '';
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordUsername'],
                ]);
        });

        it('returns 400 when discordUsername is only whitespace', function () {
            $payload = discordConnectPayload();
            $payload['discordUsername'] = '   ';
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordUsername'],
                ]);
        });

        it('returns 400 when discordId is non-numeric', function () {
            $payload = discordConnectPayload();
            $payload['discordId'] = 'abc123';
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordId'],
                ]);
        });

        it('returns 400 when discordId is too short', function () {
            $payload = discordConnectPayload();
            $payload['discordId'] = '1234567890123'; // 13 digits
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordId'],
                ]);
        });

        it('returns 400 when discordId is too long', function () {
            $payload = discordConnectPayload();
            $payload['discordId'] = '12345678901234567890123'; // >19 digits
            $response = discordConnect($this, $payload);

            $response->assertStatus(400)
                ->assertJsonPath('error', 'Validation failed')
                ->assertJsonStructure([
                    'errors' => ['discordId'],
                ]);
        });
    });

    describe('success (200)', function () {
        it('returns success when code exists and is not expired', function () {
            $user = alice($this);
            $code = givenDiscordConnectCodeForUser($this, $user);

            $resp = discordConnectWithCode($this, $code);

            $resp->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'userId',
                    'roles',
                ])
                ->assertJsonPath('success', true)
                ->assertJsonPath('userId', $user->id);

            $roles = $resp->json('roles');
            $this->assertIsArray($roles);
            $this->assertContains(Roles::USER_CONFIRMED, $roles);
        });
    });
});
