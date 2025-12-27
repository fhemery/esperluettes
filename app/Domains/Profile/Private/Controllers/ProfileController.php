<?php

namespace App\Domains\Profile\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Profile\Private\Requests\UpdateProfileRequest;
use App\Domains\Profile\Private\Services\ProfileService;
use App\Domains\Profile\Private\Services\ProfileAvatarUrlService;
use App\Domains\Shared\ViewModels\BreadcrumbViewModel;
use App\Domains\Shared\ViewModels\PageViewModel;
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
     * Display the specified user's profile (default tab based on context).
     * For own profile: shows stories tab. For others: shows about tab.
     */
    public function show(Profile $profile): View
    {
        $isOwn = Auth::check() && $this->profileService->canEditProfile(Auth::user()->id, $profile->user_id);
        $activeTab = $isOwn ? 'stories' : 'about';
        
        return $this->renderProfile($profile, $activeTab);
    }

    /**
     * Display the about tab of a user's profile.
     */
    public function showAbout(Profile $profile): View
    {
        return $this->renderProfile($profile, 'about');
    }

    /**
     * Display the stories tab of a user's profile.
     */
    public function showStories(Profile $profile): View
    {
        return $this->renderProfile($profile, 'stories');
    }

    /**
     * Display the comments tab of a user's profile.
     */
    public function showComments(Profile $profile): View
    {
        return $this->renderProfile($profile, 'comments');
    }

    /**
     * Render the profile page with the specified active tab.
     */
    private function renderProfile(Profile $profile, string $activeTab): View
    {
        $isOwn = Auth::check() && $this->profileService->canEditProfile(Auth::user()->id, $profile->user_id);
        $isModerator = $this->authApi->hasAnyRole([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]);

        $this->adjustProfilePicture($profile);
        $this->adjustProfileRoles($profile);

        return view('profile::pages.show', compact('profile', 'isOwn', 'isModerator', 'activeTab'));
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
        // Build PageViewModel with breadcrumbs
        $trail = BreadcrumbViewModel::FromHome(Auth::check());
        $trail->push(__('profile::show.title', ['name' => $profile->display_name]), route('profile.show.own'));
        $trail->push(__('profile::show.edit_profile'), null, true);

        $page = PageViewModel::make()
            ->withTitle(__('profile::edit.title', ['name' => $profile->display_name]))
            ->withBreadcrumbs($trail);

        return view('profile::pages.edit', [
            'profile' => $profile,
            'user' => $user,
            'page' => $page,
        ]);
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
                ->route('profile.show.own')
                ->with('success', __('profile::edit.updated'));
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
