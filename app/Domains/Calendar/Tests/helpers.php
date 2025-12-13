<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Public\Api\CalendarPublicApi;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;
use App\Domains\Calendar\Public\Contracts\ActivityToCreateDto;
use Tests\TestCase;
use App\Domains\Calendar\Private\Models\Activity;
require_once __DIR__ . '/Feature/Jardino/helpers.php';
require_once __DIR__ . '/Feature/SecretGift/helpers.php';

class FakeActivityRegistration implements ActivityRegistrationInterface
{
    public function displayComponentKey(): string
    {
        return 'calendar::test-fake-empty';
    }

    public function configComponentKey(): ?string
    {
        return null;
    }
}


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
 * Ensure a fake activity type is registered for tests.
 */
function registerFakeActivityType(CalendarRegistry $registry, string $key = 'fake'): void
{
    if (! $registry->has($key)) {
        $registry->register($key, new FakeActivityRegistration());
    }

    // No need to register a class-based component when returning a view key
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
    registerFakeActivityType($registry, 'fake');

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

function updateActivityStartDate(int $activityId, DateTimeInterface $startDate): void
{
    $activity = Activity::findOrFail($activityId);
    $activity->active_starts_at = $startDate;
    $activity->save();
}

function updateActivityEndDate(int $activityId, DateTimeInterface $endDate): void
{
    $activity = Activity::findOrFail($activityId);
    $activity->active_ends_at = $endDate;
    $activity->save();
}

function updateActivityVisibilityStartDate(int $activityId, DateTimeInterface $startDate): void
{
    $activity = Activity::findOrFail($activityId);
    $activity->preview_starts_at = $startDate;
    $activity->save();
}
