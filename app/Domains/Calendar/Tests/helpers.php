<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityToCreateDto;
use Tests\TestCase;

function makeActivityCreateDto(array $overrides = []): ActivityToCreateDto
{
    $base = [
        'name' => 'My Test Activity',
        'activity_type' => 'fake',
        'description' => null,
        'image_path' => null,
        'role_restrictions' => [Roles::USER, Roles::USER_CONFIRMED],
        'requires_subscription' => false,
        'max_participants' => null,
        'preview_starts_at' => null,
        'active_starts_at' => null,
        'active_ends_at' => null,
        'archived_at' => null,
    ];
    return new ActivityToCreateDto(...array_merge($base, $overrides));
}

/**
 * Create an activity through the public API. If no actor id is provided,
 * we will create and authenticate an admin user.
 */
function createActivity(TestCase $t, array $overrides = [], ?int $actorUserId = null): int
{
    /** @var CalendarRegistry $registry */
    $registry = app(CalendarRegistry::class);
    // Ensure the 'fake' type is registered for tests
    if (!method_exists($registry, 'has') || !$registry->has('fake')) {
        $registry->register('fake', new class { });
    }

    /** @var CalendarPublicApi $api */
    $api = app(CalendarPublicApi::class);

    $actorId = $actorUserId;
    if ($actorId === null) {
        $adminUser = admin($t);
        $t->actingAs($adminUser);
        $actorId = $adminUser->id;
    }

    $dto = makeActivityCreateDto($overrides);
    return $api->create($dto, $actorId);
}
