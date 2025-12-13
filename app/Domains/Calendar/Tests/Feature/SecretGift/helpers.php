<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftRegistration;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\ShuffleService;
use App\Domains\Calendar\Private\Models\Activity;
use Tests\TestCase;

/**
 * Create an ACTIVE Secret Gift activity and return helper data.
 * Returns an object: { id: int, url: string, activity: Activity }
 */
function createActiveSecretGift(TestCase $t, array $overrides = [], ?int $actorUserId = null): object
{
    $baseOverrides = [
        'name' => 'Secret Santa',
        'activity_type' => SecretGiftRegistration::ACTIVITY_TYPE,
        'requires_subscription' => true,
        'preview_starts_at' => now()->subDay(),
        'active_starts_at' => now()->subHour(),
        'active_ends_at' => now()->addDay(),
    ];

    $id = createActivity($t, overrides: array_merge($baseOverrides, $overrides), actorUserId: $actorUserId);
    $activity = Activity::findOrFail($id);
    $url = route('calendar.activities.show', $activity->slug);

    return (object) [
        'id' => $id,
        'url' => $url,
        'activity' => $activity,
    ];
}

/**
 * Create an ENDED Secret Gift activity.
 */
function createEndedSecretGift(TestCase $t, array $overrides = [], ?int $actorUserId = null): object
{
    return createActiveSecretGift($t, array_merge([
        'preview_starts_at' => now()->subDays(3),
        'active_starts_at' => now()->subDays(2),
        'active_ends_at' => now()->subHour(),
    ], $overrides), $actorUserId);
}

/**
 * Register a user as a participant in a Secret Gift activity.
 */
function registerSecretGiftParticipant(int $activityId, int $userId, ?string $preferences = null): SecretGiftParticipant
{
    return SecretGiftParticipant::create([
        'activity_id' => $activityId,
        'user_id' => $userId,
        'preferences' => $preferences,
    ]);
}

/**
 * Register multiple users as participants.
 * Returns array of SecretGiftParticipant models.
 */
function registerSecretGiftParticipants(int $activityId, array $userIds, ?string $defaultPreferences = null): array
{
    $participants = [];
    foreach ($userIds as $userId) {
        $participants[] = registerSecretGiftParticipant($activityId, $userId, $defaultPreferences);
    }
    return $participants;
}

/**
 * Perform the shuffle for an activity.
 * Returns the number of participants shuffled.
 */
function shuffleSecretGift(Activity $activity): int
{
    $service = app(ShuffleService::class);
    return $service->performShuffle($activity);
}

/**
 * Get the assignment where the given user is the giver.
 */
function getSecretGiftAssignmentAsGiver(int $activityId, int $userId): ?SecretGiftAssignment
{
    return SecretGiftAssignment::where('activity_id', $activityId)
        ->where('giver_user_id', $userId)
        ->first();
}

/**
 * Get the assignment where the given user is the recipient.
 */
function getSecretGiftAssignmentAsRecipient(int $activityId, int $userId): ?SecretGiftAssignment
{
    return SecretGiftAssignment::where('activity_id', $activityId)
        ->where('recipient_user_id', $userId)
        ->first();
}

/**
 * Save a text gift for an assignment.
 */
function saveSecretGiftText(SecretGiftAssignment $assignment, string $text): void
{
    $assignment->gift_text = $text;
    $assignment->save();
}

/**
 * Create a complete Secret Gift setup with participants and shuffled assignments.
 * Returns an object with all relevant data.
 */
function createShuffledSecretGift(TestCase $t, array $userIds, array $activityOverrides = []): object
{
    $result = createActiveSecretGift($t, $activityOverrides);

    registerSecretGiftParticipants($result->id, $userIds);
    shuffleSecretGift($result->activity);

    return $result;
}
