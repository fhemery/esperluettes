<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Services;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Domains\Calendar\Public\Contracts\ActivityState;

class SecretGiftService
{
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

    public function saveGiftImage(SecretGiftAssignment $assignment, UploadedFile $file): string
    {
        // Delete old image if exists
        if ($assignment->gift_image_path) {
            Storage::disk('local')->delete($assignment->gift_image_path);
        }

        $extension = $file->getClientOriginalExtension();
        $path = "calendar/secret-gift/{$assignment->activity_id}/{$assignment->giver_user_id}.{$extension}";

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $assignment->gift_image_path = $path;
        $assignment->save();

        return $path;
    }

    public function removeGiftImage(SecretGiftAssignment $assignment): void
    {
        if ($assignment->gift_image_path) {
            Storage::disk('local')->delete($assignment->gift_image_path);
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
        if ($assignment->recipient_user_id === $userId && $isEnded) {
            return true;
        }

        return false;
    }
}
