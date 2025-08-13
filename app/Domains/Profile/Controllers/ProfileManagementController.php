<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Profile\Requests\UpdateProfileRequest;
use App\Domains\Profile\Requests\UploadProfilePictureRequest;
use App\Domains\Profile\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileManagementController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show the form for editing the current user's profile.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $profile = $this->profileService->getOrCreateProfileByUserId($user->id);

        return view('profile::edit', compact('profile', 'user'));
    }

    /**
     * Update the current user's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $this->profileService->updateProfile($user, $request->validated());

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

    /**
     * Upload a new profile picture.
     */
    public function uploadPicture(UploadProfilePictureRequest $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $this->profileService->uploadProfilePicture($user, $request->file('profile_picture'));

            return redirect()
                ->route('profile.edit')
                ->with('success', __('Profile picture uploaded successfully!'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['profile_picture' => __('Failed to upload profile picture. Please try again.')]);
        }
    }

    /**
     * Delete the current user's profile picture.
     */
    public function deletePicture(): RedirectResponse
    {
        $user = Auth::user();
        $deleted = $this->profileService->deleteProfilePicture($user);

        if ($deleted) {
            return redirect()
                ->route('profile.edit')
                ->with('success', __('Profile picture deleted successfully!'));
        }

        return redirect()
            ->route('profile.edit')
            ->with('info', __('No profile picture to delete.'));
    }
}
