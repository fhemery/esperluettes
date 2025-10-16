<?php

namespace App\Domains\Profile\Private\Controllers;

use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Private\Services\ProfileService;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Profile\Public\Events\AvatarModerated;
use App\Domains\Profile\Public\Events\AboutModerated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class ProfileModerationController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
        private EventBus $eventBus,
    ) {
    }

    public function removeImage(Profile $profile): RedirectResponse
    {
        $deleted = $this->profileService->deleteProfilePicture($profile);
        if ($deleted) {
            $this->eventBus->emit(new AvatarModerated(
                userId: $profile->user_id,
                profilePicturePath: null,
            ));
        }
        return redirect()->back()->with('success', __('profile::moderation.remove_image.success'));
    }

    public function emptyAbout(Profile $profile): RedirectResponse
    {
        $changed = $this->profileService->emptyAbout($profile);
        if ($changed) {
            $this->eventBus->emit(new AboutModerated(userId: $profile->user_id));
        }
        return redirect()->back()->with('success', __('profile::moderation.empty_about.success'));
    }

    public function emptySocial(Profile $profile): RedirectResponse
    {
        //$this->profileService->emptySocial($profile);
        return redirect()->back()->with('success', __('profile::moderation.empty_social.success'));
    }
}
