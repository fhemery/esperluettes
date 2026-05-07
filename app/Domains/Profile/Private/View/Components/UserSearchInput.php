<?php

namespace App\Domains\Profile\Private\View\Components;

use App\Domains\Profile\Private\Services\ProfileAvatarUrlService;
use App\Domains\Profile\Private\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserSearchInput extends Component
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $value = null,
        public readonly ?string $initialDisplayName = null,
        public readonly bool $required = false,
    ) {}

    public function render(): View
    {
        $initialAvatarUrl = null;

        if ($this->value !== null) {
            $profiles = app(ProfileService::class);
            $avatars = app(ProfileAvatarUrlService::class);
            $profile = $profiles->getProfilesByUserIds([$this->value])[$this->value] ?? null;
            if ($profile) {
                $initialAvatarUrl = $avatars->publicUrl($profile->profile_picture_path, $this->value);
            }
        }

        return view('profile::components.user-search-input', [
            'searchUrl' => route('profiles.search'),
            'initialAvatarUrl' => $initialAvatarUrl,
        ]);
    }
}
