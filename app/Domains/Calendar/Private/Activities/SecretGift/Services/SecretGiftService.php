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
    public function __construct(private readonly MediaPublicApi $media)
    {
    }

    public function getParticipant(int $activityId, int $userId): ?SecretGiftParticipant
    {
        return SecretGiftParticipant::where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->first();
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
