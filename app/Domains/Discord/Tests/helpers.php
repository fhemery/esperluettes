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

/**
 * GET /api/discord/notifications/pending with default API headers
 */
function discordGetPendingNotifications($test, array $query = [], array $headers = [])
{
    $defaultHeaders = [
        'Authorization' => 'Bearer __test_api_key__',
        'Accept' => 'application/json',
    ];
    $url = '/api/discord/notifications/pending';
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }
    return $test->getJson($url, array_replace($defaultHeaders, $headers));
}

/**
 * POST /api/discord/notifications/mark-sent with default API headers
 */
function discordMarkSent($test, array $notifications, array $headers = [])
{
    $defaultHeaders = [
        'Authorization' => 'Bearer __test_api_key__',
        'Accept' => 'application/json',
    ];
    return $test->postJson(
        '/api/discord/notifications/mark-sent',
        ['notifications' => $notifications],
        array_replace($defaultHeaders, $headers)
    );
}

/**
 * Link a user to Discord using the connect flow. Returns the discordId used.
 */
function linkDiscord($test, $user, string $discordId = '111222333444555666', string $username = 'TestUser'): string
{
    $code = givenDiscordConnectCodeForUser($test, $user);
    discordConnectWithCode($test, $code, [
        'discordId'       => $discordId,
        'discordUsername' => $username,
    ])->assertStatus(200);
    return $discordId;
}

/**
 * Create a pending Discord notification row + recipients directly via the repository.
 * Returns the created DiscordPendingNotification model.
 */
function queueDiscordNotification(int $notificationId, array $recipients): \App\Domains\Discord\Private\Models\DiscordPendingNotification
{
    $repo    = app(\App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository::class);
    $pending = $repo->createPending($notificationId);
    $repo->createRecipients($pending->id, $recipients);
    return $pending;
}
