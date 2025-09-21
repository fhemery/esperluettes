<?php

namespace App\Domains\Profile\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Private\Requests\UpdateProfileRequest;
use App\Domains\Profile\Private\Services\ProfileService;
use App\Domains\Profile\Private\Services\ProfileAvatarUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
        private ProfileAvatarUrlService $avatarUrlService,
        private AuthPublicApi $authApi
    ) {
    }

    /**
     * Display the specified user's profile.
     */
    public function show(Profile $profile): View
    {
        $isOwn = Auth::check() && $this->profileService->canEditProfile(Auth::user()->id, $profile->user_id);

        $this->adjustProfilePicture($profile);
        $this->adjustProfileRoles($profile);

        return view('profile::pages.show', compact('profile', 'isOwn'));
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

        return view('profile::pages.edit', compact('profile', 'user'));
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
        $profile->roles = $this->authApi->getRolesByUserIds([$profile->user_id])[$profile->user_id] ?? [];
    }
}
