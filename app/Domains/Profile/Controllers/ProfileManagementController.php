<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Profile\Requests\UpdateProfileRequest;
use App\Domains\Profile\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileManagementController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {
    }

    /**
     * Show the form for editing the current user's profile.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $profile = $this->profileService->getProfile($user->id);

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
}
