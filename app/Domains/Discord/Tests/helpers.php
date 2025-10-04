<?php
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Build a valid payload for POST /api/discord/auth/connect.
 *
 * @param string $code One-time code (defaults to a valid-looking hex)
 * @return array{code:string,discordId:string,discordUsername:string}
 */
function discordConnectPayload(string $code = 'deadbeef'): array
{
    return [
        'code' => $code,
        'discordId' => '123456789012345678',
        'discordUsername' => 'DisplayName',
    ];
}

/**
 * Arrange: there exists a connect code for the given user.
 * For now this only generates/returns a valid-looking code; persistence is added in a later slice.
 */
function givenDiscordConnectCodeForUser($test, Authenticatable $user): string
{
    $codeResponse = $test->actingAs($user)->postJson('/discord/connect/code');
    return $codeResponse->json('code');
}

/**
 * POST /api/discord/auth/connect with provided payload.
 * Adds default Authorization/Accept headers suitable for tests unless overridden.
 */
function discordConnect($test, array $payload, array $headers = [])
{
    $defaultHeaders = [
        'Authorization' => 'Bearer __test_api_key__',
        'Accept' => 'application/json',
    ];
    return $test->postJson('/api/discord/users', $payload, array_replace($defaultHeaders, $headers));
}

/**
 * Convenience: connect using just a code; builds a valid payload via discordConnectPayload().
 */
function discordConnectWithCode($test, string $code, array $overrides = [], array $headers = [])
{
    $payload = array_replace(discordConnectPayload($code), $overrides);
    return discordConnect($test, $payload, $headers);
}

function discordGetUser($test, string $discordId, array $headers = [])
{
    $defaultHeaders = [
        'Authorization' => 'Bearer __test_api_key__',
        'Accept' => 'application/json',
    ];
    return $test->getJson('/api/discord/users/' . $discordId, array_replace($defaultHeaders, $headers));
}

/**
 * DELETE /api/discord/users/{discordId} with default API headers
 */
function discordDeleteUser($test, string $discordId, array $headers = [])
{
    $defaultHeaders = [
        'Authorization' => 'Bearer __test_api_key__',
        'Accept' => 'application/json',
    ];
    return $test->deleteJson('/api/discord/users/' . $discordId, [], array_replace($defaultHeaders, $headers));
}
        