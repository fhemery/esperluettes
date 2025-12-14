<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\View\Components;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftService;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\ShuffleService;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class SecretGiftComponent extends Component
{
    public function __construct(
        public Activity $activity,
        private readonly SecretGiftService $service,
        private readonly ShuffleService $shuffleService,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    public function render(): View
    {
        $userId = (int) Auth::id();
        $state = $this->activity->state;

        $isActive = $state === ActivityState::ACTIVE;
        $isEnded = $state === ActivityState::ENDED || $state === ActivityState::ARCHIVED;
        $isPreview = $state === ActivityState::PREVIEW;

        $participant = $this->service->getParticipant($this->activity->id, $userId);
        $isParticipant = $participant !== null;

        $assignmentAsGiver = null;
        $assignmentAsRecipient = null;
        $recipientProfile = null;
        $giverProfile = null;

        if ($isParticipant && $this->shuffleService->hasBeenShuffled($this->activity)) {
            $assignmentAsGiver = $this->service->getAssignmentAsGiver($this->activity->id, $userId);
            $assignmentAsRecipient = $this->service->getAssignmentAsRecipient($this->activity->id, $userId);

            if ($assignmentAsGiver) {
                $recipientParticipant = $this->service->getParticipant(
                    $this->activity->id,
                    (int) $assignmentAsGiver->recipient_user_id
                );
                $profiles = $this->profileApi->getPublicProfiles([$assignmentAsGiver->recipient_user_id]);
                $recipientProfile = $profiles[$assignmentAsGiver->recipient_user_id] ?? null;
            }

            if ($assignmentAsRecipient && $isEnded) {
                $profiles = $this->profileApi->getPublicProfiles([$assignmentAsRecipient->giver_user_id]);
                $giverProfile = $profiles[$assignmentAsRecipient->giver_user_id] ?? null;
            }
        }

        return view('secret-gift::components.secret-gift', [
            'activity' => $this->activity,
            'isParticipant' => $isParticipant,
            'isActive' => $isActive,
            'isEnded' => $isEnded,
            'isPreview' => $isPreview,
            'assignmentAsGiver' => $assignmentAsGiver,
            'assignmentAsRecipient' => $assignmentAsRecipient,
            'recipientProfile' => $recipientProfile,
            'recipientPreferences' => $assignmentAsGiver 
                ? $this->service->getParticipant($this->activity->id, (int) $assignmentAsGiver->recipient_user_id)?->preferences 
                : null,
            'giverProfile' => $giverProfile,
        ]);
    }
}
