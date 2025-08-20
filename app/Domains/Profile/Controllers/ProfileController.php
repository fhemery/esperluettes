<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Auth\PublicApi\UserPublicApi;
use App\Domains\Profile\Models\Profile;
use App\Domains\Profile\Requests\UpdateProfileRequest;
use App\Domains\Profile\Services\ProfileService;
use App\Domains\Profile\Services\ProfileAvatarUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
        private ProfileAvatarUrlService $avatarUrlService,
        private UserPublicApi $userPublicApi
    ) {
    }

    /**
     * Display the specified user's profile.
     */
    public function show(Profile $profile): View
    {
        $canEdit = Auth::check() && $this->profileService->canEditProfile(Auth::user()->id, $profile->user_id);

        $this->adjustProfilePicture($profile);
        $this->adjustProfileRoles($profile);

        return view('profile::show', compact('profile', 'canEdit'));
    }

    /**
     * Display the current user's profile.
     */
    public function showOwn(): View
    {
        $user = Auth::user();
        $profile = $this->profileService->getProfile($user->id);
        return $this->show($profile);
    }

    /**
     * Show the form for editing the current user's profile.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $profile = $this->profileService->getProfile($user->id);
        $this->adjustProfilePicture($profile);
        $this->adjustProfileRoles($profile);

        return view('profile::edit', compact('profile', 'user'));
    }

    /**
     * Update the current user's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $data = $request->validated();
            $file = $request->file('profile_picture');
            $remove = (bool) $request->boolean('remove_profile_picture');
            $this->profileService->updateProfileWithPicture($user->id, $data, $file, $remove);

            return redirect()
                ->route('profile.edit')
                ->with('success', __('Profile updated successfully!'));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function adjustProfilePicture(Profile $profile): void
    {
        $profile->profile_picture_path = $this->avatarUrlService->publicUrl($profile->profile_picture_path, $profile->user_id);
    }

    private function adjustProfileRoles(Profile $profile): void
    {
        $profile->roles = $this->userPublicApi->getRolesByUserIds([$profile->user_id])[$profile->user_id] ?? [];
    }
}
