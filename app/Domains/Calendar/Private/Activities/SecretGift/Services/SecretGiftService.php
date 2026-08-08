<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Services;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Domains\Calendar\Public\Contracts\ActivityState;

class SecretGiftService
{
    public function __construct(
        private readonly MediaPublicApi $media,
        private readonly ShuffleService $shuffle,
    ) {
    }

    public function getParticipant(int $activityId, int $userId): ?SecretGiftParticipant
    {
        return SecretGiftParticipant::where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Enrol a user, or overwrite the preferences of an already-enrolled one.
     *
     * `updateOrCreate` rather than `create`: the unique key on
     * `(activity_id, user_id)` would turn a double submit into a 500, and a
     * second join is semantically the same as saving preferences again.
     */
    public function join(Activity $activity, int $userId, ?string $preferences): SecretGiftParticipant
    {
        return SecretGiftParticipant::query()->updateOrCreate(
            ['activity_id' => $activity->id, 'user_id' => $userId],
            ['preferences' => $preferences],
        );
    }

    public function updatePreferences(SecretGiftParticipant $participant, ?string $preferences): void
    {
        $participant->preferences = $preferences;
        $participant->save();
    }

    /** Un-enrol a user. A no-op when there is nothing to remove. */
    public function leave(Activity $activity, int $userId): void
    {
        SecretGiftParticipant::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Un-enrol a removed (deactivated or deleted) user from every Secret Gift
     * they had joined but that has not been shuffled yet.
     *
     * Once the shuffle has run the row and its assignments are left strictly
     * alone (decision #10): the pairing stands, and whoever was drawn to
     * receive from this user simply gets no gift. That mirrors the existing,
     * deliberately unaddressed Jardino behaviour on the same events.
     *
     * Unscoped by activity on purpose — a handful of concurrent Secret Gifts
     * makes the scan cheap; revisit if that stops holding.
     */
    public function removeParticipantsForUser(int $userId): void
    {
        $participants = SecretGiftParticipant::query()
            ->with('activity')
            ->where('user_id', $userId)
            ->get();

        foreach ($participants as $participant) {
            $activity = $participant->activity;

            if ($activity !== null && $this->shuffle->hasBeenShuffled($activity)) {
                continue;
            }

            $participant->delete();
        }
    }

    public function getAssignmentAsGiver(int $activityId, int $userId): ?SecretGiftAssignment
    {
        return SecretGiftAssignment::where('activity_id', $activityId)
            ->where('giver_user_id', $userId)
            ->first();
    }

    public function getAssignmentAsRecipient(int $activityId, int $userId): ?SecretGiftAssignment
    {
        return SecretGiftAssignment::where('activity_id', $activityId)
            ->where('recipient_user_id', $userId)
            ->first();
    }

    public function saveGiftText(SecretGiftAssignment $assignment, ?string $text): void
    {
        $assignment->gift_text = $text;
        $assignment->save();
    }

    /**
     * Store a gift image on Media's private disk and point the row at it.
     * The previous file is left alone: Media GC reclaims it once no row claims it.
     */
    public function saveGiftImage(SecretGiftAssignment $assignment, UploadedFile $file): string
    {
        $path = $this->media->storePrivate('secret-gift/' . $assignment->activity_id, $file);

        $assignment->gift_image_path = $path;
        $assignment->save();

        return $path;
    }

    /** Clears the reference only — the file is Media GC's to delete. */
    public function removeGiftImage(SecretGiftAssignment $assignment): void
    {
        if ($assignment->gift_image_path) {
            $assignment->gift_image_path = null;
            $assignment->save();
        }
    }

    public function canViewImage(SecretGiftAssignment $assignment, int $userId, Activity $activity): bool
    {
        // Giver can always see their own image
        if ($assignment->giver_user_id === $userId) {
            return true;
        }

        // Recipient can see after activity ends
        $state = $activity->state;
        $isEnded = $state === ActivityState::ENDED || $state === ActivityState::ARCHIVED;

        return $assignment->recipient_user_id === $userId && $isEnded;
    }

    public function saveGiftSound(SecretGiftAssignment $assignment, UploadedFile $file): string
    {
        // Delete old sound if exists
        if ($assignment->gift_sound_path) {
            Storage::disk('local')->delete($assignment->gift_sound_path);
        }

        $extension = $file->getClientOriginalExtension();
        $path = "calendar/secret-gift/{$assignment->activity_id}/sound-{$assignment->giver_user_id}-" . time() . ".{$extension}";

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $assignment->gift_sound_path = $path;
        $assignment->save();

        return $path;
    }

    public function removeGiftSound(SecretGiftAssignment $assignment): void
    {
        if ($assignment->gift_sound_path) {
            Storage::disk('local')->delete($assignment->gift_sound_path);
            $assignment->gift_sound_path = null;
            $assignment->save();
        }
    }

    public function canViewSound(SecretGiftAssignment $assignment, int $userId, Activity $activity): bool
    {
        // Giver can always see their own sound
        if ($assignment->giver_user_id === $userId) {
            return true;
        }

        // Recipient can see after activity ends
        $state = $activity->state;
        $isEnded = $state === ActivityState::ENDED || $state === ActivityState::ARCHIVED;

        return $assignment->recipient_user_id === $userId && $isEnded;
    }
}
