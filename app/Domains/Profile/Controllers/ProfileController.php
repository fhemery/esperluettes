<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Profile\Models\Profile;
use App\Domains\Profile\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {
    }

    /**
     * Display the specified user's profile.
     */
    public function show(Profile $profile): View
    {
        $user = $profile->user;
        $canEdit = Auth::check() && $this->profileService->canEditProfile(Auth::user(), $profile);

        return view('profile::show', compact('profile', 'user', 'canEdit'));
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
}
