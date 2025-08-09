<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Auth\Models\User;
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
        $this->middleware('auth');
    }

    /**
     * Display the specified user's profile.
     */
    public function show(User $user): View
    {
        $profile = $this->profileService->getOrCreateProfileByUserId($user->id);
        $canEdit = Auth::check() && $this->profileService->canEditProfile(Auth::user(), $profile);

        return view('profile::show', compact('profile', 'user', 'canEdit'));
    }

    /**
     * Display the current user's profile.
     */
    public function showOwn(): View
    {
        return $this->show(Auth::user());
    }
}
