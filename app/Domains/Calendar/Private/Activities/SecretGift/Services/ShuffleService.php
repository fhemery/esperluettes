<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Services;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Support\Facades\DB;

class ShuffleService
{
    public function performShuffle(Activity $activity): int
    {
        $participants = SecretGiftParticipant::where('activity_id', $activity->id)
            ->pluck('user_id')
            ->shuffle()
            ->values();

        if ($participants->count() < 2) {
            throw new \InvalidArgumentException('At least 2 participants are required for shuffling.');
        }

        DB::transaction(function () use ($activity, $participants) {
            // Clear any existing assignments for this activity
            SecretGiftAssignment::where('activity_id', $activity->id)->delete();

            // Create circular assignments
            foreach ($participants as $index => $giverUserId) {
                $recipientUserId = $participants[($index + 1) % $participants->count()];

                SecretGiftAssignment::create([
                    'activity_id' => $activity->id,
                    'giver_user_id' => $giverUserId,
                    'recipient_user_id' => $recipientUserId,
                ]);
            }
        });

        return $participants->count();
    }

    public function hasBeenShuffled(Activity $activity): bool
    {
        return SecretGiftAssignment::where('activity_id', $activity->id)->exists();
    }
}
