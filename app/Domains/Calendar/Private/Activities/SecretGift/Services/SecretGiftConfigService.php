<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Services;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftSettings;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything the admin configures on a Secret Gift — today the single
 * registration deadline that rides along with the activity form — plus the
 * registration-window predicate every participant-facing screen consults.
 *
 * Controllers and view components go through this service; neither touches a
 * model.
 */
class SecretGiftConfigService
{
    public function __construct(
        private readonly ShuffleService $shuffle,
        private readonly ProfilePublicApi $profiles,
    ) {}

    public function settingsFor(int $activityId): ?SecretGiftSettings
    {
        return SecretGiftSettings::query()->where('activity_id', $activityId)->first();
    }

    /** Create or update the activity's settings row. */
    public function saveSettings(int $activityId, mixed $registrationEndsAt): SecretGiftSettings
    {
        return SecretGiftSettings::query()->updateOrCreate(
            ['activity_id' => $activityId],
            ['registration_ends_at' => Carbon::parse($registrationEndsAt)],
        );
    }

    /**
     * Enrolment is open during the preview phase only, until the declared
     * deadline or the shuffle — whichever comes first.
     *
     * **A missing settings row means closed.** The deadline is mandatory on the
     * admin form, so its absence means the activity was created around that form
     * and has no declared window; refusing enrolment is the fail-safe answer.
     */
    public function isRegistrationOpen(Activity $activity): bool
    {
        if ($activity->state !== ActivityState::PREVIEW) {
            return false;
        }

        $settings = $this->settingsFor($activity->id);
        if ($settings === null) {
            return false;
        }

        if (! Carbon::now()->lessThan($settings->registration_ends_at)) {
            return false;
        }

        return ! $this->shuffle->hasBeenShuffled($activity);
    }

    /**
     * The activity's participants as public profiles, ordered by display name.
     *
     * Never carries `preferences`: that column is only ever read for the one
     * participant a giver has been assigned to.
     *
     * @return Collection<int,ProfileDto>
     */
    public function participantsWithProfiles(int $activityId): Collection
    {
        $userIds = SecretGiftParticipant::query()
            ->where('activity_id', $activityId)
            ->pluck('user_id')
            ->all();

        if ($userIds === []) {
            return collect();
        }

        // One call for the whole list — no per-participant lookup.
        return collect($this->profiles->getPublicProfiles($userIds))
            ->filter()
            ->sortBy(fn (ProfileDto $profile) => $profile->display_name)
            ->values();
    }
}
